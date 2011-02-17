<?php
return array(	
    // {{{ $_TAG->dummynoparams : dummy class with no params
    'gld_dummynoparams' => array(
        'params'            => 0,
        'construct'         => array('dummy'),
        'version'           => 1,
        ),
    // }}}
    // {{{ $_TAG->dummyparams['a'] : dummy class with 1 param
    'gld_dummyparams' => array(
        'params'            => 1,
        'construct'         => array('dummy', '_X_create_object'),
        'version'           => 1,
        ),
    // }}}
    // {{{ $_TAG->dummyparams['a']['b'] : dummy class with 2 params
    'gld_dummyparams2' => array(
        'params'            => 2,
        'construct'         => array('dummy', '_X_create_object'),
        'version'           => 1,
        ),
    // }}}
    // {{{ $_TAG->dummy2noparams : dummy2 class with no params
    'gld_dummy2noparams' => array(
        'params'            => 0,
        'construct'         => array('dummy2'),
        'version'           => 1,
        ),
    // }}}
    // {{{ $_TAG->dummychild : dummychild class with no params
    'gld_dummychild'        => array(
        'params'            => 0,
        'construct'         => array('dummyChild'),
        'version'           => 1,
        ),
    );	
    // }}}
?>
