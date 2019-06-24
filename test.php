<?php

class Testing {
    
    var $obj = "original";
    
    function __construct() {
        $this->print();
    }
    
    function assign (&$obj) {
        $this->obj = &$obj;
    }
    
    function print () {
        print_r($this->obj);
        echo "<br/>";
    }
    
}
$test2 = array(2);
$testing = new Testing();
$testing->assign($test2);
$test2 []= 5;
        
echo "<br/>";
print_r($test2);
echo "<br/>";
$testing->print();


foreach (array(1=>"apple",2=>"pears") as $key => $value) {
    echo $key." = ".$value."<br/>";
}










