<?php

/*
    * To change this license header, choose License Headers in Project Properties.
     * To change this template file, choose Tools | Templates
      * and open the template in the editor.
       */

require_once "../vendor/autoload.php";

$input = "12345678";

$regex = "#^(?<first>([0-9]{1,3})??)(?<others>([0-9]{3})*)$#";

$callback = function($match) {
        $arr = str_split($match["others"], 3);
            return empty($match["first"]) ? (implode(",", $arr)) : ("" . $match["first"] . "," . implode(",", $arr));
};

$output = preg_replace_callback($regex, $callback, $input);

var_dump($output);
