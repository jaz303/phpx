<?php
class MyController extends Controller
{
    eval {
        $class->before_filter('auth_check');
    }
    
    protected function auth_check() {
        echo "<p>Performing auth check";
    }
}
?>