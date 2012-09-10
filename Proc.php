<?php

class Kizano_Proc
{
	const STDIN = 0;
	const STDOUT = 1;
	const STDERR = 2;

	protected
		$_proc,
		$_pipes = array(),
		$_descriptor = array(
			0 => array("pipe", "r"), // stdin is a pipe to us.
			1 => array("pipe", "w"), // stdout is a pipe to us.
			2 => array("pipe", "r"), // stderr is a pipe to us.
		);

	public function __construct($cmd, $cwd = null, $env = null, $opts = null) {
		$this->_proc = proc_open($cmd, $this->_descriptor, $this->_pipes, realpath($cwd), $env, $opts);
		if ( !$this->_proc || !is_resource($this->_proc) ) {
			throw new RuntimeException("Could not open $cmd for execution.");
		}
	}

	public function __destruct() {
		foreach ($this->_pipes as $pipe) {
			
		}
	}

	public function read($bytes = null) {
		return fread($this->_pipes[self::STDIN], $bytes);
	}

	public function write($what, $bytes = null) {
		is_null($bytes) && $bytes = strlen($what);
		return fwrite($this->_pipes[self::STDOUT], $what, $bytes);
	}

	public function terminate($signal) {
		proc_terminate($this->_proc, $signal);
	}

	public function kill($s) { return $this->terminate($s); }

	public function status() {
		return proc_get_status($this->_proc);
	}

	public function getPipes() {
		return $this->_pipes;
	}

	public function setDescriptor($index, array $what) {
		if ( !is_numeric($index) || !preg_match('!^\d*$!', $index) ) {
			throw new RuntimeException('Pipe index is not a valid file descriptor.');
		}

		$this->_descriptor[$index] = $what;
		return $this;
	}
}

