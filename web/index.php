<?php

use Pecee\Http\Middleware\Exceptions\TokenMismatchException;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\SimpleRouter;

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/pecee/simple-router/helpers.php');

function getXpath($endpoint = ''): DOMXPath {
    $contents = file_get_contents(getLink($endpoint));

    $domdocument = new DOMDocument();
    @$domdocument->loadHTML($contents);

    return new DOMXPath($domdocument);
}

function getLink($endpoint = '') {
    $BASE_URL = 'https://www18.gogoanime.io';

    return "$BASE_URL$endpoint";
}

function getCachedAnime() {
    $animeCacheContents = null;
    $animeCache = __DIR__ . '/../app/cache/anime.json';
    $animeCacheTime = 86400;
    $updateCache = true;

    if (file_exists($animeCache)) {
        $animeCacheContents = file_get_contents($animeCache);
        $animeCacheContents = json_decode($animeCacheContents, true);

        ['time' => $time, 'anime' => $anime] = $animeCacheContents;

        if ($time < time() + $animeCacheTime) {
            $updateCache = false;
        }
    }

    if ($updateCache) {
        $anime = [];
        $currentPage = 1;

        $selectorToXpath = new Symfony\Component\CssSelector\CssSelectorConverter();
        $xpathQuery = $selectorToXpath->toXPath('ul.listing > li');

        while (true) {
            $xpath = getXpath("/anime-list.html?page=$currentPage");
            $response = $xpath->query($xpathQuery);

            if ($response->count()) {
                for ($i = 0; $i < $response->count(); $i++) {
                    $html = $response->item($i)->attributes->getNamedItem('title')->nodeValue;
                    $href = $response->item($i)->childNodes->item(1)->attributes->getNamedItem('href')->nodeValue;
                    $href = getLink($href);

                    $anime[] = str_replace('<a class="bigChar" href="">', '<a class="bigChar" href="'.$href.'" target="_blank">', $html);
                }

                $currentPage++;
            } else {
                break;
            }
        }

        $animeCacheData = json_encode([
            'time' => time() + $animeCacheTime,
            'anime' => $anime,
        ]);
        file_put_contents($animeCache, $animeCacheData);
    }

    return $anime;
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

    SimpleRouter::get('/get/{animeId}', function (string $animeId) {
        $anime = getCachedAnime();

        echo '<a href="/random">Get Random</a><br><br>' . $anime[$animeId];
    });

    SimpleRouter::get('/random', function () {
        $anime = getCachedAnime();
        response()->redirect('/get/' . array_rand($anime));
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
