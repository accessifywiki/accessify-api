/*!
  A basic parser/validator for the Google App Engine `app.yaml`.
  @author  Nick Freear, 23 February 2016.
*/

var fs = require('fs')
  , yaml = require('yaml')
  , yaml_filename = '../app.yaml'
  , yaml_string;

try {
    yaml_string = fs.readFileSync(yaml_filename).toString();
    yaml.eval(yaml_string);
} catch (ex) {
    console.log('Error. YAML parser - "' + ex.message + '" - ' + yaml_filename);
    process.exit(1);
}

console.log('OK. YAML parser success - ' + yaml_filename);
process.exit(0);

//End.
