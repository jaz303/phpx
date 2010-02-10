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
// Loader

class PHPX
{
    //
    // Don't rely on an autlooader to load phpx compiler
    
    private static $phpx_loaded = false;
    
    //
    //
    
    private static $stack = array();
    
    //
    //
    
    public static function init() {
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
    
    public static function load($file) {
        
        $file = self::resolve_file_in_include_path($file);
        if ($file === null) {
            return false;
        }
        
        if (in_array($file, self::$stack)) {
            trigger_error(
                "phpx: cyclic dependencies encountered while attempting to load $file",
                E_USER_ERROR
            );
        }
        
        $source = file_get_contents($file);
        if ($source === false) {
            return false;
        }
        
        self::init();
        
        try {
            
            self::$stack[] = $file;

            $parser         = new Parser;
            $source_tree    = $parser->parse($source);
            
            foreach ($source_tree->get_defined_classes() as $class_def) {
                $class_def->finalise();
            }
            
            eval('?>' . $source_tree->to_php());

            Library::register_file($source_tree);
            
            array_pop(self::$stack);
            
        } catch (\Exception $e) {
            
            array_pop(self::$stack);
            
        }
        
        return true;
        
    }
    
    // no idea why i need to write this method myself, PHP should do it for me.
    private static function resolve_file_in_include_path($file) {
        $candidates = explode(PATH_SEPARATOR, get_include_path());
        foreach ($candidates as $dir) {
            $path = realpath($dir . '/' . $file);
            if ($path && is_readable($path)) {
                return $path;
            }
        }
        return null;
    }
}
?>