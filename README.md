## MykiLeaks - Overcharge assessment for myki.com.au statements

This project is the core PHP library that powers the http://mykileaks.org website. For more
information visit http://monofonik.net/2011/09/the-mykileaks-faq/.

As well as the class library, a CLI tool is also provided for assessing myki statements in PDF
format from the command line. With the required pre-requisites available it can be used on both
Linux and OS X.


### Pre-requisites

- PHP 5.6+
- [composer](https://getcomposer.org/)
- The CLI requires that `pdftotext` is installed and on you `$PATH`. It is used to convert the 
  original PDF statement to plain text.


### CLI usage

```bash
$ php mykileaks.php /path/to/statement.pdf
```

Results are output in JSON format. [jq](http://stedolan.github.io/jq/) can be used to filter and/or
format the output.


### Library usage

Add `monofonik/mykileaks` as a dependency to your application's composer.json:

```json
{
    "require": {
        "monofonik/mykileaks": "dev-master"
    },
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/monofonik/mykileaks.git",
        }
    ]
}
```


Install:

    composer install


Require the generated autoload.php file and you're good to go:

```php
    <?php 

    require "/path/to/project/vendor/autoload.php";
    $statement = file_get_contents("/path/to/statement.txt");
    $submission = new MykiLeaks\Submission(new MyliLeaks\Auditor());
    $assessment = $submission->submit($statement);
    echo json_encode($assessment);

```
