<?php
namespace phpx;

if (!defined('PHPX_INIT')) {
    /**
     * Set this to the name of a function to be called after PHPX has loaded its
     * core libraries. Its primary use is to register custom macros.
     */
    define('PHPX_INIT', false);
}

if (!defined('PHPX_USE_INCLUDE_PATH')) {
    /**
     * Set to true to search the PHP include path when loading files
     */
    define('PHPX_USE_INCLUDE_PATH', false);
}

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
            require_once "$d/source.php";
            require_once "$d/arguments.php";
            require_once "$d/parser.php";
            require_once "$d/library.php";
            require_once "$d/macros.php";
            
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
    
    private $cached     = false;
    private $fd         = null;
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        
        $file_path  = substr($path, 7); // strip leading phpx://
        $flags      = 0;
        
        if (strpos($mode, 'r') === false) {
            return false;
        }
        
        if ($options & STREAM_USE_PATH) {
            $flags |= FILE_USE_INCLUDE_PATH;
        }
        
        if (!$source = file_get_contents($file_path, $flags)) {
            return false;
        }
        
        self::load_phpx();
        
        if (in_array($file_path, self::$stack)) {
            trigger_error("phpx: cyclic dependencies encountered while attempting to load $file_path");
        }
        
        try {
            
            self::$stack[] = $file_path;

            $parser         = new Parser;
            $source_tree    = $parser->parse($source);
            $this->parsed   = $source_tree->to_php();
            $this->len      = strlen($this->parsed);

            Library::register_file($source_tree);
            
            array_pop(self::$stack);
            
        } catch (\Exception $e) {
            
            array_pop(self::$stack);
            
        }
        
        return true;
        
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