<?php

return [
    'closureProp' => function ($param1, $param2) {
        return $param1 . ':' . $param2;
    },
    'closureProp2' => function () {
        return 'No Param';
    },
    'closureArray' => function ($param) {
        return is_array($param);
    },
    'closureWithoutArgs' => function () {
        return true;
    }
];
