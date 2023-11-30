<?php

global $logFile;

function logInit($file) { global $logFile; $logFile=$file; }

function logStart($file) {
  ($fh = fopen($file, "a")) or die("Unable to Open Journal");
  return $fh;
  }

function logClose($file) {
  fclose($file);
  }

function logWrite($message) {
  global $logFile;
  $now = date("Y/m/d H:i:s");
  $fh = logStart($logFile);
  fprintf($fh, "\n%s:\n", $now);
  $res = print_r($message, true);
  fprintf($fh, "%s\n", $res);
  logClose($fh);
  }

?>
