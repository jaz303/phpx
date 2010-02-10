phpx - compile-time metaprogramming for PHP
===========================================

&copy; 2010 Jason Frame [ [jason@onehackoranother.com](mailto:jason@onehackoranother.com) / [@jaz303](http://twitter.com/jaz303) ]  
Released under the MIT License.

A lot of attempts have been made at providing Ruby-like features to PHP. All of the ones
I've seen involve a bunch of hacks - such as requiring extension of a single base class, mixin registries, reliance on `__call()` - most of which carry a significant performance penalty at runtime. They don't result in particularly elegant source code either, but hey, this is PHP.

One Giant Hack to Rule Them All
-------------------------------

phpx uses a different approach - it creates a superset of PHP's syntax, adding new, expressive features, and implements a simple compiler that converts this new syntax into standard PHP.

How it works
------------

    "I'm gonna twist ya, and I'm gonna flip ya.
    Every time you squeal, I'm just gonna go faster and harder."

phpx splits PHP source code into a token stream (using PHP's standard library function `token_get_all()`), then scans the stream for syntax that would normally be invalid but has been co-opted by phpx to provide additional functionality. Internally, for each class that phpx processes, an internal representation is created that can be inspected, augmented, rearranged, and of course, transformed to raw, valid PHP.

It must be stressed that all of this happens at **compile time** - that is, an autoload hook
intercepts the request for the class and delegates its loading to phpx's custom stream loader.
The resulting transformed source code can then be cached so that loading the class in subsequent requests carries **absolutely no performance penalty**.

Mandatory pre-alpha warning
---------------------------

This is pre-alpha software. Don't sit behind your monitor cursing me if it doesn't work.
If you use it for anything mission critical at this stage you're bonkers, and we should probably
be working together.

Whirlwind Feature Tour
----------------------

Here's a quick overview of all of phpx's syntax features:

  * Doc-comment annotations for associating key/value pairs with classes and methods
    (values can be any valid JSON fragment):
  
        /**
         * (shortcut for :model = true)
         * :model
         */
        class MyModel {
            /**
             * Build up an array over multiple lines for clarity:
             * :access[] = "admin"
             * :access[] = "public"
             */
            public static void find_all() {
                // ...
            }
        }
        
        // access class annotations
        var_dump(phpx\\annotations_for('MyModel'));
        var_dump(phpx\\annotations_for(new MyModel));
        // => array("model" => true)
        
        // access method annotations
        var_dump(phpx\\annotations_for('MyModel', 'find_all'));
        var_dump(phpx\\annotations_for(new MyModel, 'find_all'));
        // => array("access" => array("admin", "public"))
        
  * Mixins - instance methods, static methods and constants can all be mixed in separately:
  
        class InstanceMethods {
            public function foo() { echo "foo!\n"; }
        }
        
        class StaticMethods {
            public static function bar() { echo "bar!\n"; }
        }
        
        class Constants {
            const BAZ = 'baz';
        }
        
        class Foo {
            include InstanceMethods;
            include static StaticMethods;
            include const Constants;
        }
        
        $thing = new Foo;
        $thing->foo();
        Foo::bar();
        echo Foo::BAZ;
  
  * Interface delegation -
    marking a member variable as implementing an interface (or comma-separated list of interfaces)
    will generate proxy methods delegating those interfaces' calls accordingly:
  
        interface Recipient {
            public function send_to($email);
        }
        
        class Person implements Recipient {
            public function send_to($email) {
                echo "Sending message to $email...\n";
            }
        }
        
        class Addressable {
            protected $person implements \Recipient;
            public Addressable() {
                $this->person = new Person();
            }
        }
        
        $foo = new Addressable();
        $foo->send_to('jason@onehackoranother.com');
  
  * Compile time evaluation - inside a class body, an `eval {}` block will have its contents
    executed, with class's `ClassDef` instance made available through the `$class` variable.
    `ClassDef` instances provide methods for defining methods/instance variables/constants,
    adding mixins and implementing interfaces:

        class Foo {
            eval {
                //
                // default access modifiers to apply to any methods/variables created
                // inside.
                $class->with_access('public', function($class) {
                    //
                    // "define_static_method" is dynamic - can use any valid combination
                    // of public, protected, private, static, instance (negates static),
                    // method, variable and constant.
                    $class->define_static_method(
                        'add', // method name
                        '$a, $b', // arg list
                        'return $a + $b;' // method body
                    );
                    // creates instance variable $foo, get_foo() and set_foo()
                    $class->attr_accessor('foo');
                });
            }
            //
            // overwrite macro-generated setter:
            public function set_foo($f) {
                $this->foo = strtoupper($f);
            }
        }

        echo Foo::add(5, 10);
        // => 15

        $foo = new Foo;
        $foo->set_foo("hello world");
        echo $foo->get_foo();
        // => "HELLO WORLD"
  
  * Macros - the `$class` object passed to class-level evaluations can be extended with macros
    encapsulating discreet, possibly parameterised, operations. For example we can concoct
    something similar to Rails' `belongs_to`:
  
        class BelongsToMacro {
            public function apply($class, $model) {
                $class->define_public_instance_method("get_$model", "return null;");
                $class->define_public_instance_method("build_$model", "return;");
                $class->define_public_instance_method("create_$model", "return;");
            }
        }
        
        // all macros must be registered
        phpx\register_macro('belongs_to', 'BelongsToMacro');
        
        class MyModel {
            eval {
                $class->belongs_to('associated_model');
            }
        }
        
  * Pattern-matching - provide method names as regular expressions and any matching calls will
    be dispatched accordingly, along with an array of sub-pattern matches.
    
        class Foo {
            public function "/^(render|display)_template$"($template_name, $matches) {
                if ($matches[1] == 'render') {
                    echo "rendering: $template_name";
                } else {
                    echo "displaying: $template_name";
                }
            }
            //
            // you may define as many patterns as you wish...
            public function "/^find_(people|managers|employees)$/"($matches) {
                // ...
            }
        }
        
        $foo = new Foo();
        $foo->render_template('bar');
        
Quick Start Guide
-----------------

phpx requires PHP 5.3.

To quickly see it in action, hit the `demo` directory in a browser.

To try out phpx in your own apps, copy the `lib` directory into your project, and copy/modify the contents of `boot.php` to a suitable location in your application, either in its own file or within some other file that you load at the start of each request. The purpose of `boot.php` is twofold: first, to load the phpx runtime, and second, to set up the autoloader to load new classes using phpx.

phpx is lazy loading; that is, it won't `require()` its compiler backend until it's called upon to load some code. A corollary to this is that the macro registration function `phpx\register_macro($macro_name, $macro_class)` will not be available either. Two solutions:

  * Demand that phpx load the whole backend by calling `phpx\Stream::load_phpx();` - you'll
    then have access to `phpx\register_macro()`. This is fine for
    testing but having to load the backend every time kinda defeats the purpose of lazy loading.
    Which leads us to the second solution:
  * Wrap you macro registrations in a function, and define `PHPX_INIT` to be said function's
    name. phpx will call this function, lazily, as part of its initialisation procedure.
    
Note that the function for querying annotations (`phpx\annotations_for($class [, $method])`) is loaded as part of the runtime and is always available.

Caveats
-------

Right now, phpx's basic unit of processing is a file. The main ramification of this is that phpx in unable to reflect (either using PHP's native reflection or it's own internal representation) on any types defined in the same source file as it is currently processing. This could technically be solved by introducing incremental evaluation, `eval()`'ing and registering each class/interface as it was defined.

When using namespaces, it's best to use fully qualified references everywhere as phpx isn't smart enough to fix references when, for example, mixing in code.

Tests
-----

There's a fairly decent test suite which can be invoked from the command line:

    jason@ratchet phpx [master*] $ php run_tests.php
    ...........................................................

    Summary: 59/59 tests passed, 183/183 assertions
    0.014956s
    

TODO, aka "a call for contributors"
-----------------------------------

phpx opens up a pile of a new possibilities for concise PHP programming but there's a lot still to be done.

The biggy:

  * The aforementioned caching mechanism for mitigating the performance penalty associated
    with parsing and processing the raw source code unfortunately does not yet exist. There's
    nothing technically difficult about this, I was just itching to get this project released
    given the recent buzz around Facebook's hippity-hopscotch thingamajig. Ideal mechanism would
    be to memoize the list of sources used to compile each output file, and perform invalidation
    accordingly.
    
Nice to haves:

  * Possibility for restricting macro availability to certain classes
    (e.g. only classes extending `ActiveRecord` would get the `belongs_to()` macro)
  * Should be possible to merge annotations through inheritance tree
  * <del>Should be possible to add annotations to member variables as well as methods</del>
  * Pattern-matching should work with inheritance
  * Pattern-matching should work statically
  * Pattern-matching should tolerate existing __call() implementation
  * Introduce guard blocks to prevent method clobbering
  * Ability to rename/copy class members
  * Ability to query a class's included mixins
  * Error handling is generally poor and should be improved
  * Tests - a good few exist but a lot is not covered
  * <del>Stream wrappers are currently used to handle script loading. I can't remember why I took this approach and I'm not sure it's any better than simply reading the file using `file_get_contents()`</del>
  * <del>Forward declarations - we'll never be able to support Ruby's open classes but we can get pretty close by providing hooks to pre-register class mutations and call them at the appropriate time e.g.:</del>
  
        // closure form - lambda would be run as class eval {} block
        phpx\forward("SomeClass", function($class) { });
        
        // proxy object - a bunch of chainable methods for recording operations for
        // later replay
        phpx\forward("SomeClass")->mixin("Foobar");
  
Bug Reporting, Feature Requests
-------------------------------

Please use the Github issue tracker for reporting bugs.  
Feel free to contact me at jason@onehackoranother.com regarding anything else related to this project.