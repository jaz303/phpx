<?php
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
?>