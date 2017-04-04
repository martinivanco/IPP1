<?php
/* File: syn.php                       *
 * Project: IPP 1, Syntax highlighting *
 * Author: Martin Ivanco (xivanc03)    */

/* Auxiliary function for checking arguments. */
function argCheck() {
	global $argc, $argv, $formatfname, $inputfname, $outputfname, $brline; 

	if ($argv[1] == "--help") {
		if ($argc != 2) {
			$ster = fopen('php://stderr', 'a');
			fwrite($ster, "ERROR: Parameter \"--help\" can't be combined with other arguments.\n");
			fclose($ster);
			die(1);
		}
		echo "Script for automatic highlighting of different parts of text.\nValid arguments:\n --help\t\t\tDisplays this help.\n --format=filename\tSpecifiing format file. Format file contains format\n\t\t\tentries. Format entry consists of a regular expression\n\t\t\tand formatting commands.\n --input=filename\tSpecifiing input file.\n --output=filename\tSpecifiing output file.\n --br\t\t\tAdds element <br /> at the end of each line.\n";
		die(0);
	}

	for ($i = 1; $i < $argc; $i++) { 
		if (substr($argv[$i], 0, 9) == "--format=") {
			if ($formatfname == NULL) {
				$formatfname = substr($argv[$i], 9);
			} else {
				$ster = fopen('php://stderr', 'a');
				fwrite($ster, "ERROR: Multiple format file arguments.\n");
				fclose($ster);
				die(1);
			}
		} elseif (substr($argv[$i], 0, 8) == "--input=") {
			if ($inputfname == NULL) {
				$inputfname = substr($argv[$i], 8);
			} else {
				$ster = fopen('php://stderr', 'a');
				fwrite($ster, "ERROR: Multiple input file arguments.\n");
				fclose($ster);
				die(1);
			}
		} elseif (substr($argv[$i], 0, 9) == "--output=") {
			if ($outputfname == NULL) {
				$outputfname = substr($argv[$i], 9);
			} else {
				$ster = fopen('php://stderr', 'a');
				fwrite($ster, "ERROR: Multiple output file arguments.\n");
				fclose($ster);
				die(1);
			}
		} elseif ($argv[$i] == "--br") {
			if ($brline == false) {
				$brline = true;
			} else {
				$ster = fopen('php://stderr', 'a');
				fwrite($ster, "ERROR: Multiple \"--br\" arguments.\n");
				fclose($ster);
				die(1);
			}
		} else {
			$ster = fopen('php://stderr', 'a');
			fwrite($ster, "ERROR: Unknown argument \"$argv[$i]\".\n");
			fclose($ster);
			die(1);
		}
	}
}

/******************** FUNCTIONS ********************/

/* Makes a thorough check regarding negation. */
function nFlagCheck($str) {
	global $nFlag;
	if ($nFlag) {
		if (($str == '|') || ($str == '*') || ($str == '+') || ($str == '(') || ($str == ')')) {
			die(4);
		}
		if ($str == '.') {
			return "";
		}
		$nFlag = false;
		return "[^$str]";
	} else {
		return $str;
	}
}

/* Creates search word for preg_match function from given regular expression. */
function makeSearchWord($search) {
	global $nFlag, $oFlag;
	$nFlag = false;
	$oFlag = false;
	$searchWord = "";
	for ($i = 0; $i < strlen($search); $i++) {	//parsing char by char
		$part = $search[$i];
		switch ($part) {
			case "%":
				$part = $search[++$i];
				switch ($part) {				//escape switch
					case "a":
						$searchWord .= nFlagCheck(".");
						break;
					case "d":
						$searchWord .= nFlagCheck("[0-9]");
						break;
					case "l":
						$searchWord .= nFlagCheck("[a-z]");
						break;
					case "L":
						$searchWord .= nFlagCheck("[A-Z]");
						break;
					case "w":
						$searchWord .= nFlagCheck("[A-Za-z]");
						break;
					case "W":
						$searchWord .= nFlagCheck("[0-9A-Za-z]");
						break;
					case "s":
					case "t":
					case "n":
					case ".":
					case "|":
					case "*":
					case "+":
					case "(":
					case ")":
						$searchWord .= nFlagCheck("\\$part");
						break;
					case "%":
					case "!":
						$searchWord .= nFlagCheck($part);
						break;
					default:
						die(4);
						break;
				}								//end of escape switch
				$oFlag = false;
				break;

			case '\\':
			case '/':
			case '^':
			case '$':
			case '[':
			case ']':
			case '?':
			case '{':
			case '}':
				$searchWord .= nFlagCheck("\\$part");
				$oFlag = false;
				break;
			case '.':
				if ($nFlag || $oFlag) {
					die(4);
				}
				$oFlag = true;
				break;
			case '!':
				$nFlag = true;
				break;
			case '|':
				if ($oFlag) {
					die(4);
				}
				$oFlag = true;
				$searchWord .= nFlagCheck($part);
				break;
			default:
				$searchWord .= nFlagCheck($part);
				$oFlag = false;
				break;
		}
	}											//end of main loop
	return "/$searchWord/s";
}

/* Adds given text to the end of string at given position in global array. */
function addToArrayAsLast($pos, $text) {
	global $positions;
	$positions[$pos] .= $text;
}

function addToArrayAsFirst($pos, $text) {
	global $positions;
	$positions[$pos] = "$text$positions[$pos]";
}

/* Finds all instances of given expression in global input and then adds given marks
 * to the global array appropriately. */
function findAll($searchWord, $lmark, $rmark) {
	global $input;
	preg_match_all($searchWord, $input, $matches, PREG_OFFSET_CAPTURE);
	foreach ($matches[0] as $match) {
		if (strlen($match[0]) > 0) {
			addToArrayAsLast($match[1], $lmark);
			addToArrayAsFirst(($match[1] + strlen($match[0])), $rmark);
		}
	}
}

/* Adds <br /> marks before each '\n' symbol in global input. */
function addLineBreaks() {
	global $input;
	$offset = 0;
	while (($offset = strpos($input, "\n", $offset)) !== false) {
		addToArrayAsLast($offset, "<br />");
		$offset += 1;
	}
}

/* Creates a begin and end mark from a given command. */
function makeMarks($command) {
	switch ($command) {
		case "bold":
			$marks = array("<b>", "</b>");
			break;
		case "italic":
			$marks = array("<i>", "</i>");
			break;
		case "underline":
			$marks = array("<u>", "</u>");
			break;
		case "teletype":
			$marks = array("<tt>", "</tt>");
			break;
		default:								//does nothing and continues checking below
			break;
	}

	if (substr($command, 0, 5) == "size:") {
		$number = substr($command, 5);
		if (ctype_digit($number)) {
			$number = (int)$number;
			if (($number > 0) && ($number < 8)) {
				$marks = array("<font size=$number>", "</font>");
			} else {
				die(4);
			}
		} else {
			die(4);
		}
	}

	if (substr($command, 0, 6) == "color:") {
		$color = substr($command, 6);
		if (ctype_xdigit($color) && (strlen($color) == 6)) {
			$marks = array("<font color=#$color>", "</font>");
		} else {
			die(4);
		}
	}

	if ($marks != NULL) {
		return $marks;
	} else {
		die(4);
	}
}

/* Divides single format entry into search word and commands and executes them. */
function parseFormatEntry($entry) {
	if (($entry == "") || ($entry == "\n")) {
		return;
	}
	$tpos = strpos($entry, "\t");
	$search = substr($entry, 0, $tpos);
	$rest = substr($entry, $tpos);
	$rest = ltrim($rest);

	if (($search == "") || ($rest == "")) {
		$ster = fopen('php://stderr', 'a');
		fwrite($ster, "ERROR: Wrong format file.\n");
		fclose($ster);
		die(4);
	}

	$searchWord = makeSearchWord($search);
	$commands = explode(",", $rest);
	$length = count($commands);
	for ($i = 0; $i < $length; $i++) { 
		$commands[$i] = trim($commands[$i]);
		$marks = makeMarks($commands[$i]);
		findAll($searchWord, $marks[0], $marks[1]);
	}
}

/* Creates output out of input according to marks written in global array. */
function highlight() {
	global $length, $positions, $input, $output;
	$prevptr = 0;
	for ($currptr = 0; $currptr <= $length; $currptr++) { 
		if ($positions[$currptr] != NULL){
			$output .= substr($input, $prevptr, ($currptr-$prevptr));
			$output .= $positions[$currptr];
			$prevptr = $currptr;
		}
	}
	$output .= substr($input, $prevptr);
}

/******************** MAIN PART ********************/

$formatfname = NULL;							//global argument variables
$inputfname = NULL;
$outputfname = NULL;
$brline = false;

argCheck();

if ($inputfname == NULL) {						//getting input
	$input = file_get_contents("php://stdin");
	$length = strlen($input);
} else {
	if (($file = fopen($inputfname, "r")) == false) {
		$ster = fopen('php://stderr', 'a');
		fwrite($ster, "ERROR: Unable to open file \"$inputfname\".\n");
		fclose($ster);
		die(2);
	}
	$length = filesize($inputfname);
	$input = fread($file, $length);
	fclose($file);
}

if ($outputfname != NULL) {
	$file = fopen($outputfname, "w");				//creating or rewriting output
	fclose($file);
}

$fentry = ""; #format entry variable
$nFlag = false; #negation flag - needs to be global for nFlagCheck function
$oFlag = false; #operator flag - needs to be global for checking regex
$output = "";
$positions = array(); #array for marks

if ($formatfname != NULL) {						//format handling
	if (($file = fopen($formatfname, "r")) != false) {
		while (!feof($file)) {
			$fentry = fgets($file);
			parseFormatEntry($fentry);
		}
	}
	else {
		$ster = fopen('php://stderr', 'a');
		fwrite($ster, "WARNING: Unable to open file \"$formatfname\".\n");
		fclose($ster);
	}
}

if ($brline == true) {
	addLineBreaks();
}

highlight();

if ($outputfname == NULL) {						//exporting output
	echo $output;
} else {
	if (($file = fopen($outputfname, "w")) == false) {
		$ster = fopen('php://stderr', 'a');
		fwrite($ster, "ERROR: Unable to open file \"$outputfname\".\n");
		fclose($ster);
		die(3);
	}
	fwrite($file, $output);
	fclose($file);
}

?>