<?php
/**
 * Extension der Template Engine für Subdomain 'austria'
 *
 * + Bitte beachten ! Alle Funktionen haben den Prefix 'austria_'
 *
 * Bsp.: Aufruf im View
 * {function="austria_test(3, 4)"}
 *
 * @author Stephan Krauss
 * @date 20.05.2014
 * @file templateExtensions.php
 * @project HOB
 * @package tool
 */

function austria_test($a, $b)
{
    return $a + $b;
}