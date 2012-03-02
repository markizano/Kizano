<?php
/**
 * Kizano/Misc.php
 *
 * PHP version 5
 *
 * Namespace placeholder for functions that would normally be free-floating.
 *  @copyright  Copyright (c) 2011 W3Evolutions <http://www.w3evolutions.com>
 *
 * This class is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Kizano
 * @package   Miscelaneous
 * @author    Markizano Draconus <markizano at markizano dot net>
 * @license   http://www.gnu.org/licenses/gpl.html GNU Public License
 * @link      https://github.com/markizano/Kizano/blob/master/Misc.php
 */

error_reporting(E_ALL | E_STRICT);
/**
 *	Namespace for miscelaneous functions that would normally be free-floating.
 *  @author Markizano Draconus <markizano at markizano dot net>
 */
class Kizano_Misc
{
    /**
     * Debugging variables and configurable option.
     */
    public static $registerDebug = false;
    protected static $_debugRegistered = false;
    protected static $_debug = array();

	/**
	 *	Gets a Console-printable string representation of the current backtrace.
	 *	@return		String	A Console-printable backtrace
	 */
	public static function textBacktrace($backtrace = null)
	{
	    if ($backtrace === null) {
    		$backtrace = self::backtrace();
    		array_shift($backtrace);
		}
		$result = null;
		if (count($backtrace))
			foreach ($backtrace as $back) {
				isset($back['class']) || $back['class'] = 'Static';
				isset($back['type']) || $back['type'] = '::';
				isset($back['file']) || $back['file'] = 'php://magic';
				isset($back['line']) || $back['line'] = '00';
				$result .= "<$back[file]:$back[line]> ".
					"$back[class]$back[type]".
					"$back[function]("
				;
				$comma = false;
				if (count($back['args']))
					foreach ($back['args'] as $args) {
						$result .= $comma? ', ': null;
						$comma || $comma = true;
						if (is_string($args)) {
							$result .= "'$args'";
						} elseif (is_numeric($args)) {
							$type = gettype($args);
							$result .= "($type) $args";
						} elseif (is_array($args)) {
							$args = preg_replace(
							    array("/\r\n?/", '/(\w+)\s+\(/'),
							    array("\n", '\1 ('),
							    print_r($args, true)
						    );
							$result .= "(array) $args";
						} elseif (is_object($args)) {
							$type = gettype($args);
							if (is_callable(array($args, '__toString'))) {
								$args = $args->__toString();
							} else{
								$args = get_class($args);
							}
							$result .= "($type) $args";
						} elseif (is_bool($args)) {
							$args = $args? 'true': 'false';
							$result .= "(boolean) $args";
						} elseif (is_null($args)) {
							$result .= "null";
						} else{
							$type = gettype($args);
							$result .= "($type) [object]";
						}
					}
				$result .= ");\n";
			}
		return $result;
	}

	/**
	 *	Gets a Console-printable string representation of the current backtrace.
	 *	@return		String	A Console-printable backtrace
	 */
	public static function consoleBacktrace($backtrace = null)
	{
	    defined('STDOUT') || define('STDOUT', fOpen('php://stdout', 'a'));
	    if ($backtrace === null) {
    		$backtrace = self::backtrace();
    		array_shift($backtrace);
		}
		$result = null;
		if (count($backtrace))
			foreach ($backtrace as $back) {
				isset($back['class']) || $back['class'] = 'Static';
				isset($back['type']) || $back['type'] = '::';
				isset($back['file']) || $back['file'] = 'php://magic';
				isset($back['line']) || $back['line'] = '00';
				$result .= "<\033[31m$back[file]\033[00m:\033[01;30m$back[line]\033[00;00m> ".
					"\033[34m$back[class]\033[00m$back[type]".
					"\033[34m$back[function]\033[00m("
				;
				$comma = false;
				if (count($back['args']))
					foreach ($back['args'] as $args) {
						$result .= $comma? ', ': null;
						$comma || $comma = true;
						if (is_string($args)) {
							$result .= "\033[31m'$args'\033[00m";
						} elseif (is_numeric($args)) {
							$type = gettype($args);
							$result .= "(\033[32m$type\033[00m) $args";
						} elseif (is_array($args)) {
							$args = preg_replace(
							    array("/\r\n?/", '/(\w+)\s+\(/'),
							    array("\n", '\1 ('),
							    print_r($args, true)
						    );
							$result .= "(\033[32marray\033[00m) $args";
						} elseif (is_object($args)) {
							$type = gettype($args);
							if (is_callable(array($args, '__toString'))) {
								$args = $args->__toString();
							} else{
								$args = get_class($args);
							}
							$result .= "(\033[32m$type\033[00m) $args";
						} elseif (is_bool($args)) {
							$args = $args? 'true': 'false';
							$result .= "(\033[32mboolean\033[00m) $args";
						} elseif (is_null($args)) {
							$result .= "\033[31mnull\033[00m";
						} else{
							$type = gettype($args);
							$result .= "(\033[32m$type\033[00m) [object]";
						}
					}
				$result .= ");<br />\n";
			}
		return $result;
	}

	/**
	 *	Gets a HTML-printable string representation of the current backtrace.
	 *	@return		String	A HTML-printable backtrace
	 */
	public static function htmlBacktrace($backtrace = null)
	{
	    if ($backtrace === null) {
    		$backtrace = self::backtrace();
    		array_shift($backtrace);
		}
		$result = null;
		if (count($backtrace))
			foreach ($backtrace as $back) {
				isset($back['class']) || $back['class'] = 'Static';
				isset($back['type']) || $back['type'] = '::';
				isset($back['file']) || $back['file'] = 'php://magic';
				isset($back['line']) || $back['line'] = '00';
				$result .= "&lt;<span style='color:#CC0000;'>$back[file]</span>:$back[line]&gt;&nbsp;".
					"<span style='color:#0000AA;'>$back[class]</span>$back[type]".
					"<span style='color:#0000AA;'>$back[function]</span>("
				;
				$comma = false;
				if (count($back['args']))
					foreach ($back['args'] as $args) {
						$result .= $comma? ', ': null;
						$comma || $comma = true;
						if (is_string($args)) {
							$result .= "<span style='color:#CC0000;'>'$args'</span>";
						} elseif (is_numeric($args)) {
							$type = gettype($args);
							$result .= "(<span style='color:#00CC00;'>$type</span>) $args";
						} elseif (is_array($args)) {
							$type = gettype($args);
							$args = preg_replace(
							    array("/\r\n?/", '/(\w+)\s+\(/'),
							    array("\n", '\1 ('),
							    print_r($args, true)
						    );
							$result .= "(<span style='color:#00CC00;'>$type</span>) $args";
						} elseif (is_object($args)) {
							$type = gettype($args);
							if (is_callable(array($args, '__toString'))) {
								$args = $args->__toString();
							} else{
								$args = get_class($args);
							}
							$result .= "(<span style='color:#00CC00;'>$type</span>) $args";
						} elseif (is_bool($args)) {
							$args = $args? 'true': 'false';
							$result .= "(<span style='color:#00CC00;'>boolean</span>) $args";
						} elseif (is_null($args)) {
							$result .= "<span style='color:#CC0000;'>null</span>";
						} else{
							$type = gettype($args);
							$result .= "(<span style='color:#00CC00;'>$type</span>) [object]";
						}
					}
				$result .= ");\n";
			}

		return "<pre>$result</pre><br />\n";
	}

	/**
	 * Returns a custom-created backtrace. One that doesn't include the dumping of irrelevant objects.
	 * @return	 Array   The [corrected] backtrace
	 */
	public static function backtrace()
	{
		$debug = debug_backtrace();
	    $args = array();
		if (defined('STDOUT')) {
		    $prefix = "\033[32m";
		    $suffix = "\033[00m";
		} else {
		    $prefix = "<span style='color:#00CC00;'>";
		    $suffix = "</span>";
		}
		array_shift($debug);
		array_push($debug, array(
			'file' => $_SERVER['SCRIPT_FILENAME'],
			'line' => '0',
			'class' => 'main',
			'function' => 'main',
			'args' => array(),
			
		));
		foreach ($debug as $i => $deb) {
			unset($debug[$i]['object']);
			foreach ($deb['args'] as $k => $d) {
				is_object($d) && $debug[$i]['args'][$k] = "({$prefix}object{$suffix})" . get_class($d);
				if (is_array($d)) {
				    $debug[$i]['args'][$k] = "({$prefix}array{$suffix})\n[\n\t";
				    foreach ($d as $key => $val) {
    				    $args[] = sprintf("\t$key => ({$prefix}%s{$suffix}) %s", getType($val), is_string($val)? $val: null);
				    }
				    $debug[$i]['args'][$k] .= join(",\n", $args)."\n]";
			    }
			}
		}
		return $debug;
	}

    /**
     *  Generates an easily read var_dump of the provided exception.
     *
     *  @param Exception $e     The exception to print.
     *
     *  @return String
     */
    public static function textException(Exception $e)
    {
        return sprintf(
            "Type: %s\n" .
            "Message (%d): %s\n" .
            "Location: <%s:%d>;\n" .
            "Trace: \n%s\n" .
            "Previous: %s\n",
            get_class($e), $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(),
            self::textBacktrace($e->getTrace()),
            is_null($e->getPrevious())? '<N/A>': self::textException($e->getPrevious())
        );
    }

    /**
     *  Generates an easily read var_dump of the provided exception.
     *
     *  @param Exception $e     The exception to print.
     *
     *  @return String
     */
    public static function consoleException(Exception $e)
    {
        return str_replace(array("\r\n", "\r"), "\n", sprintf(
            "Message (\033[01;34m%d\033[0m): \033[01m%s\033[0m\n" .
            "Location: <\033[31m%s\033[0m:%d>\n" .
            "Trace: \n%s\n" .
            "Previous: %s\n",
            $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(),
            self::consoleBacktrace($e->getTrace()),
            is_null($e->getPrevious())? '<N/A>': self::consoleException($e->getPrevious())
        ));
    }

    /**
     *  Generates an easily read var_dump of the provided exception.
     *
     *  @param Exception $e     The exception to print.
     *
     *  @return String
     */
    public static function htmlException(Exception $e)
    {
        return str_replace(array("\r\n", "\r"), "\n", sprintf(
            "<pre>\n" .
            "Message (<span style='color:#0000AA;'>%d</span>): <span style='font-weight:bold;'>%s</span>\n" .
            "Location: &lt;<span style='color:#AA0000;'>%s</span>:%d&gt;\n" .
            "Trace: \n%s\n" .
            "Previous: %s\n" .
            "</pre>",
            $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(),
            self::htmlBacktrace($e->getTrace()),
            is_null($e->getPrevious())? '&lt;N/A&gt;': self::htmlException($e->getPrevious())
        ));
    }

	/**
	 * Gathers all the data you could possibly want about a class.
	 * 
	 * @param String|Object   $class   The class to describe. Either the (string) name or the object itself.
	 * 
	 * @return Array
	 */
    public static function describeClass($class) {
        $r = new ReflectionClass($class);
        $props = $methods = $constants = array();
        foreach ($r->getConstants() as $name => $value)
            $constants[$name] = $value;
        foreach ($r->getProperties() as $prop)
            $props[$prop->class][] = $prop->name;
        foreach ($r->getMethods() as $meth)
            $methods[$meth->class][] = $meth->name;

        return array(
        	'constants' => $constants,
            'props' => $props,
            'methods' => $methods,
        );
    }


    /**
     * Gets a more easily read var_dump() while `html_errors` is off by stripping excess whitespace.
     * Temporarily registers data to dump at the end of the application.
     *
     * @paramList Mixed $var    A variable to dump.
     *
     * @return String
     */
    public static function var_dump()
    {
        if (self::$registerDebug && !self::$_debugRegistered) {
            register_shutdown_function(array(__CLASS__, 'dumpDebug'));
            self::$_debugRegistered = true;
        }

        ob_start();
        $html_errors = ini_get('html_errors');
        ini_set('html_errors', false);
        $args = func_get_args();
        self::$_debug[] = $args;
        array_map('var_dump', $args);
        ini_set('html_errors', $html_errors);
        return preg_replace(
            array('#=>\s+(\w)#'),
            array('=> \1'),
            ob_get_clean()
        );
    }

    /**
     * Dumps the contents of that which has been debugged. This is mainly for knowing how many times
     * {@link self::vardump} was called.
     * 
     * @return void
     */
    public static function dumpDebug()
    {
        $debug = self::$_debug;
        print PHP_EOL . PHP_EOL . self::var_dump($debug);
    }
}

