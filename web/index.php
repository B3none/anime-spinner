<?php

use Pecee\Http\Middleware\Exceptions\TokenMismatchException;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\SimpleRouter;

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/pecee/simple-router/helpers.php');

function getXpath($endpoint = ''): DOMXPath {
    $BASE_URL = 'https://www18.gogoanime.io';
    $contents = file_get_contents("$BASE_URL$endpoint");

    $domdocument = new DOMDocument();
    @$domdocument->loadHTML($contents);

    return new DOMXPath($domdocument);
}

try {
    SimpleRouter::get('/', function () {
        response()->redirect('/random');
    });

    SimpleRouter::get('/popular', function () {
        $selectorToXpath = new Symfony\Component\CssSelector\CssSelectorConverter();
        $xpathQuery = $selectorToXpath->toXPath('p.name > a');

        $xpath = getXpath('/popular.html');
        $response = $xpath->query($xpathQuery);

        if ($response->count()) {
            for ($i = 0; $i < $response->count(); $i++) {
                echo($response->item($i)->nodeValue);
                echo PHP_EOL;
            }
        } else {
            echo 'Nothing found. Check CSS selector.';
        }
    });

    SimpleRouter::get('/random', function () {
        $anime = [];
        $currentPage = 1;

        $selectorToXpath = new Symfony\Component\CssSelector\CssSelectorConverter();
        $xpathQuery = $selectorToXpath->toXPath('ul.listing > li');

        while (true) {
            $xpath = getXpath("/anime-list.html?page=$currentPage");
            $response = $xpath->query($xpathQuery);

            if ($response->count()) {
                for ($i = 0; $i < $response->count(); $i++) {
                    $anime[] = $response->item($i)->attributes->getNamedItem('title')->nodeValue;
                }

                $currentPage++;
            } else {
                break;
            }
        }

        $anime = array_unique($anime);
        echo $anime[array_rand($anime)];
    });

    SimpleRouter::start();
} catch (TokenMismatchException $e) {
    var_dump($e);
} catch (NotFoundHttpException $e) {
    var_dump($e);
} catch (\Pecee\SimpleRouter\Exceptions\HttpException $e) {
    var_dump($e);
} catch (Exception $e) {
    var_dump($e);
}
