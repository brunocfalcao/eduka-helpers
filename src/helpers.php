<?php

if (! function_exists('allow_if')) {

    /**
     * Executes the callable if the result if true.
     *
     * @param  bool  $result   The condition to be evaluated.
     * @param  callable  $callable The callable function to be executed.
     * @return void
     */
    function allow_if($result, callable $callable)
    {
        if ($result === true) {
            $callable();
        }
    }
}

if (! function_exists('allow_if_not')) {

    /**
     * Executes the callable if the result if false.
     *
     * @param  bool  $result   The condition to be evaluated.
     * @param  callable  $callable The callable function to be executed.
     * @return void
     */
    function allow_if_not($result, callable $callable)
    {
        if ($result === false) {
            $callable();
        }
    }
}

if (! function_exists('really_empty')) {
    /**
     * Check if the variable is really empty with std class empty check
     * included.
     *
     * @param  mixed  $object [description]
     * @return bool
     */
    function really_empty($content)
    {
        if (strtoupper(class_basename($content)) == 'STDCLASS') {
            return empty((array) $content);
        }

        return blank($content);
    }
}
