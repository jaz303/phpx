Rails-style Controllers
=======================

This is just a quick example demonstrating how phpx can be used to create Rails-style controllers with multiple filter chains and declarative exception handling. Check out the files in this directory to see how it all fits together. Start with `index.php`, then look at the mixin classes, and finally move onto `Controller.php`, `MyController.php` and `UsersController.php`.

Running the examples
--------------------

Hit this directory in your browser and try the following URLs:

    /
    /?action=show
    /?action=show&id=1
    /?action=new
    /?action=edit
    /?action=edit&id=1

Implementing Filters
--------------------

First we create a macro class allowing filters to be stashed as inheritable attributes on the class definition, and a couple of helper methods for assigning filters to common chains:

    class FilterMacro
    {
        public function add_filter($class, $chain, $where, $method, $options = array()) {
            $options['method'] = $method;
            if ($where == 'start') {
                $class->inheritable_array_unshift("filters__$chain", $options);
            } elseif ($where == 'end') {
                $class->inheritable_array_push("filters__$chain", $options);
            }
        }
    
        public function before_filter($class, $method, $options = array()) {
            $this->add_filter($class, 'before', 'end', $method, $options);
        }
    
        public function after_filter($class, $method, $options = array()) {
            $this->add_filter($class, 'after', 'end', $method, $options);
        }
    }
    
And here is how we'd use it. The first parameter is the name of the method containing the filter logic, and the second is an array of options to pass to the filter executor. Here we've borrowed the Rails approach of allowing filters to be applied only to certain actions.

    class UserController extends Controller {
        eval {
            $class->before_filter('find_user', array('only' => array('show', 'edit', 'delete')));
        }
        
        protected function find_user() {
            // logic to find user here
        }
    }

Next thing we need is a method to actually run the filters within class instances. For this we'll use a mixin:

    class FilterMixin
    {
        protected function run_filter_chain($chain) {
            echo "Running filter chain: $chain<br />";
            $var = "filters__$chain";
            if (isset(static::$$var)) {
                foreach (static::$$var as $filter_spec) {
                    $run = true;
                    if (isset($filter_spec['only']) && !in_array($this->action, $filter_spec['only'])) $run = false;
                    if (isset($filter_spec['except']) && in_array($this->action, $filter_spec['except'])) $run = false;
                    if ($run) {
                        $this->{$filter_spec['method']}();
                    }
                }
            }
        }
    }

Note the use of `static` to use late-static-binding to find the correct set of filters, allowing `FilterMixin` to be mixed-in in your base class but called from subclasses, with the correct filters being applied. The example contains an intermediate class, `MyController`, exemplifying this.

Implementing Declarative Exception Handling
-------------------------------------------

In the course of handling a request, one of our actions (or filters) might throw an exception, for example, when a required database record cannot be found. We want to be able to handle exceptions sensibly, i.e. display a 404 page for a missing record, or a 500 for some other error. It would be kick-ass if we could map exception classes to specific handler methods, thereby avoiding writing plumbing code to handle each case. Let's start with a macro for registering our handlers:

    class RescueMacro
    {
        public function rescue_from($class, $exception_class, $rescue_method) {
            $class->merge_inheritable_array('rescue_macro__rescues', array($exception_class => $rescue_method));
        }
    }

Pretty simple - we just stash the exception class and its associated handler method in an inheritable array. Now for the handler. Again, we'll use a mixin:

    class RescueMixin
    {
        protected function perform_with_rescue($method) {
            try {
                $this->$method();
            } catch (\Exception $exception) {
                if (isset(static::$rescue_macro__rescues)) {
                    foreach (static::$rescue_macro__rescues as $exception_class => $rescue_method) {
                        if (is_a($exception, $exception_class)) {
                            $this->$rescue_method($exception);
                            return;
                        }
                    }
                }
            }
        }
    }

So what we have is a method wraps a call to a user-supplied worker method with an exception handler which delegates exception handling to the relevant method. In our own classes, we can now do:

    class Controller
    {
        include \RescueMixin;

        eval {
            $class->rescue_from('\\RecordNotFoundException', 'rescue_not_found');
        }
        
        public function invoke_action($action, array $params) {
            $this->action = $action;
            $this->params = $params;
            $this->perform_with_rescue('perform_invoke');
        }

        protected function perform_invoke() {
            $this->run_filter_chain('before');
            $this->{"_{$this->action}"}();
            $this->run_filter_chain('after');
        }

        protected function rescue_not_found($exception) {
            echo "User not found!<br />";
        }
    }

`invoke_action` is the controller entry point, setting up the controller for handling a request and then using `perform_with_rescue` to safely call the main worked method. This pattern could easily be extend to accept parameters or anonymous functions.

That's a wrap
-------------

Hopefully this has served as a good example of a real-world application for phpx, please send any feedback or questions to the usual locations: [jason@onehackoranother.com](mailto:jason@onehackoranother.com) or [@jaz303](http://twitter.com/jaz303).
