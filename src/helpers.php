<?php

if (!function_exists('allow_if')) {

    /**
     * Executes the callable if the result if true.
     *
     * @param  bool   $result   The condition to be evaluated.
     * @param  callable $callable The callable function to be executed.
     *
     * @return void
     */
    function allow_if($result, callable $callable)
    {
        if ($result === true) {
                $callable();
        }
    }

}
