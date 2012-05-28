<?php
include 'RNG_Debugger.php';
RNG_Debugger::debugObject('I am here', 'Header');
$aArray = array(
    'me'    => 'you',
    'us'    => 'them',
);
RNG_Debugger::debugObject($aArray, 'Array Items');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
