<?php
/**
 * A basic parser/validator for the Google App Engine `app.yaml` - FAILS :(.
 * @author  Nick Freear, 20 February 2016.
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

$yaml = new Parser();
$yaml_file = './app.yaml';

try {
    $value = $yaml->parse(file_get_contents($yaml_file));
} catch (ParseException $e) {
    fprintf(STDERR, "Error. YAML parser says: '%s' - %s\n", $e->getMessage(), $yaml_file);
    exit(1);
}

fprintf(STDERR, "OK. YAML file parsed: '%s' - %s\n", $e->getMessage(), $yaml_file);

#End.
