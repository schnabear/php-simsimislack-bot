# PHP Slack Bot SimSimi Command

A SimSimi implementation on Slack using PHP Slack Bot

## Installation

Create a new composer.json file and add the following...
```
{
    "minimum-stability": "dev",
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/schnabear/php-simsimislack-bot.git"
        }
    ],
    "require": {
        "schnabear/php-simsimislack-bot": "dev-master"
    }
}
```

Then run...
```
composer install
```

## Usage

```php
require 'vendor/autoload.php';

define('SLACK_TOKEN', 'SlackToken');

$simsimi = new \PhpSimsimiSlackBot\SimsimiCommand('en');
$bot = new \PhpSlackBot\Bot();
$bot->setToken(SLACK_TOKEN);
$bot->loadCatchAllCommand($simsimi);
$bot->run();
```
