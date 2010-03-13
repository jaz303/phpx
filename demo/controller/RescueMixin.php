<?php
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
?>