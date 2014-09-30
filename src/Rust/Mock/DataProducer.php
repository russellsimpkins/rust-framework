<?php

namespace Rust\Mock;

class DataProducer {

    var $options;

    public function __constructor($option=array()) {
        $this->options=$options;
    }

    public function produceSampleSet($params=array()) {
        $card=2;
        $sample = array('results'=>array('cards'=>array(array('suit'=>'clubs',
                                                                      'value'=>$card++),
                                                        array('suit'=>'clubs',
                                                                      'value'=>$card++),
                                                        array('suit'=>'clubs',
                                                                      'value'=>$card++),
                                                        array('suit'=>'clubs',
                                                                      'value'=>$card++),
                                                        array('suit'=>'clubs',
                                                                      'value'=>$card))));
        return array(200=>$sample);
    }

    public function produceErrorSet($params=array()) {
        $card=2;
        $sample = array('results'=>array('cards'=>array(array('suit'=>'clubs',
                                                                      'value'=>$card++),
                                                        array('suit'=>'clubs',
                                                                      'value'=>$card++),
                                                        array('suit'=>'clubs',
                                                                      'value'=>$card++),
                                                        array('suit'=>'clubs',
                                                                      'value'=>$card++),
                                                        array('suit'=>'clubs',
                                                                      'value'=>$card))));
        return array(500=>$sample);
    }
}