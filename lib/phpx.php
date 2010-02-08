<?php
/*
 * phpx - compile-time metaprogramming for PHP
 *
 * (c) 2010 Jason Frame [jason@onehackoranother.com]
 */
namespace phpx;

if (!defined('PHPX_INIT')) {
    /**
     * Set this to the name of a function to be called after PHPX has loaded its
     * core libraries. Its primary use is to register custom macros.
     */
    define('PHPX_INIT', false);
}

//
// Some functions useful for the runtime as well as the compiler

function absolutize_namespace($ns) {
    return ($ns[0] == '\\') ? $ns : ('\\' . $ns);
}

function absolute_class_name($class) {
    if ($class instanceof ClassDef) {
        $class = $class->get_qualified_name();
    } elseif ($class instanceof \ReflectionClass) {
        $class = $class->getName();
    }
    return absolutize_namespace((string) $class);
}

function annotations_for($thing1, $thing2 = null) {
    if (is_object($thing1)) $thing1 = get_class($thing1);
    $thing1 = absolutize_namespace($thing1);
    if ($thing2 == null) {
        return Annotation::for_class($thing1);
    } else {
        return Annotation::for_method($thing1, $thing2);
    }
}

//
// Runtime annotation support

$GLOBALS['__PHPX_ANNOTATIONS__'] = array();

class Annotation
{
    private static $annotations = array();
    
    public static function export_class_annotation(ClassDef $def) {
        return '$GLOBALS[\'__PHPX_ANNOTATIONS__\'][\'' . $def->get_qualified_name() . '\'] = ' . var_export($def->get_annotation(), true) . ';';
    }
    
    public static function export_method_annotation(ClassDef $def, Method $method) {
        return '$GLOBALS[\'__PHPX_ANNOTATIONS__\'][\'' . $def->get_qualified_name() . ':' . $method->get_name(). '\'] = ' . var_export($method->get_annotation(), true) . ';';
    }
    
    public static function for_class($fq_class_name) { return self::get($fq_class_name); }
    public static function for_method($fq_class_name, $method_name) { return self::get("$fq_class_name:$method_name"); }
    
    private static function get($key) {
        return isset($GLOBALS['__PHPX_ANNOTATIONS__'][$key])
                    ? $GLOBALS['__PHPX_ANNOTATIONS__'][$key]
                    : array();
    }
}

//
// Stream Loader

class Stream
{
    //
    // Don't rely on an autlooader to load phpx compiler
    
    private static $phpx_loaded = false;
    
    public static function load_phpx() {
        if (!self::$phpx_loaded) {
            $d = dirname(__FILE__);
            
            require_once "$d/functions.php";
            require_once "$d/access.php";
            require_once "$d/simple.php";
            require_once "$d/members.php";
            require_once "$d/source.php";
            require_once "$d/arguments.php";
            require_once "$d/parser.php";
            require_once "$d/library.php";
            require_once "$d/macros.php";
            require_once "$d/annotation_parser.php";
            
            if (PHPX_INIT) {
                $initializer = PHPX_INIT;
                $initializer();
            }
            
            self::$phpx_loaded = true;
        }
    }
    
    //
    //
    
    private static $stack = array();
    
    //
    //
    
    public $context;
    
    private $position;
    private $parsed;
    private $len;
    
    private $cached         = false;
    private $absolute_path  = null;
    private $fd             = null;
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        
        if (strpos($mode, 'r') === false) {
            return false;
        }
        
        $this->absolute_path = substr($path, 7); // strip leading phpx://
        if ($options & STREAM_USE_PATH) {
            if (!is_readable($this->absolute_path)) {
                $this->absolute_path = $this->resolve_file_in_include_path($this->absolute_path);
                if (!$this->absolute_path) {
                    return false;
                }
            }
        }
        
        if (!$source = file_get_contents($this->absolute_path)) {
            return false;
        }
        
        $opened_path = $this->absolute_path;
        
        self::load_phpx();
        
        if (in_array($this->absolute_path, self::$stack)) {
            trigger_error(
                "phpx: cyclic dependencies encountered while attempting to load $file_path",
                E_USER_ERROR
            );
        }
        
        try {
            
            self::$stack[] = $this->absolute_path;

            $parser         = new Parser;
            $source_tree    = $parser->parse($source);
            
            foreach ($source_tree->get_defined_classes() as $class_def) {
                $class_def->finalise();
            }
            
            $this->parsed   = $source_tree->to_php();
            $this->len      = strlen($this->parsed);

            Library::register_file($source_tree);
            
            array_pop(self::$stack);
            
        } catch (\Exception $e) {
            
            array_pop(self::$stack);
            
        }
        
        return true;
        
    }
    
    // no idea why i need to write this method myself, PHP should do it for me.
    private function resolve_file_in_include_path($file) {
        $candidates = explode(PATH_SEPARATOR, get_include_path());
        foreach ($candidates as $dir) {
            $path = realpath($dir . '/' . $file);
            if ($path && is_readable($path)) {
                return $path;
            }
        }
        return null;
    }
    
    public function stream_stat() {
        return $this->cached ? fstat($this->fd) : stat($this->absolute_path);
    }
    
    public function stream_read($count) {
        if ($this->stream_eof()) {
            return false;
        } else {
            if ($this->cached) {
                return fread($this->fd, $count);
            } else {
                $remain = $this->len - $this->position;
                if ($count > $remain) {
                    $count = $remain;
                }
                $out = substr($this->parsed, $this->position, $count);
                $this->position += $count;
                return $out;
            }
        }
    }
    
    public function stream_tell() {
        if ($this->cached) {
            return ftell($this->fd);
        } else {
            return $this->position;
        }
    }
    
    public function stream_eof() {
        if ($this->cached) {
            return feof($this->fd);
        } else {
            return $this->position >= $this->len;
        }
    }
    
    public function stream_seek($offset, $whence) {
        if ($this->cached) {
            return fseek($this->fd, $offset, $whence);
        } else {
            switch ($whence) {
                case SEEK_SET:
                    $this->position = $offset;
                    break;
                case SEEK_CUR:
                    $this->position += $offset;
                    break;
                case SEEK_END:
                    $this->position = $this->len + $offset;
                    break;
            }
        }
    }
    
    public function stream_close() {
        if ($this->cached) {
            return fclose($this->fd);
        }
    }
}

if (!stream_wrapper_register('phpx', 'phpx\\Stream')) {
    trigger_error("Couldn't register phpx stream filter", E_USER_ERROR);
}
?>