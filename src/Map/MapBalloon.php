<?php

namespace Map\Map;

use Map\Infra\Html;

final class MapBalloon
{
    public function __construct(private string $photoHost)
    {
    }

    /**
     * @param list<string> $routes
     */
    public function stop(string $name, array $routes): array
    {
        $links = [];
        foreach ($routes as $code) {
            $code = trim($code);
            if ($code === '') {
                continue;
            }
            $links[] = '<a onclick="showRoute(\'' . Html::e($code) . '\')" href="#">'
                . Html::e(TransitLabel::format($code)) . '</a>';
        }
        $title = Html::e($name);
        $header = '<p class=address>' . $title . '</p>';
        $body = '<p class=address>Остановка: <b>' . $title . '</b></p>'
            . '<p class=result>Маршруты:<br>' . implode(',&nbsp;', $links) . ' </p>';
        return [$header, $body];
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string> $photos
     */
    public function address(array $row, string $link, array $photos = [], bool $withAddressCount = true): array
    {
        $town = Html::e((string) ($row['town'] ?? ''));
        $street = Html::e((string) ($row['street'] ?? ''));
        $house = Html::e((string) ($row['house'] ?? ''));
        $title = trim($town . ', ' . $street . ', ' . $house, ' ,');
        $header = '<p class=address>' . $title . '</p>';

        $body = '<p class=address>Адрес: <b>' . $title . '</b></p>';
        $district = trim((string) ($row['district'] ?? ''));
        $massive = trim((string) ($row['massive'] ?? ''));
        if ($district !== '') {
            $body .= '<p class=result>Район: <b>' . Html::e($district) . '</b></p>';
        }
        if ($massive !== '') {
            $body .= '<p class=result>Микрорайон: <b>' . Html::e($massive) . '</b></p>';
        }
        $body .= '<p class=result>';
        if ($withAddressCount) {
            $body .= 'Адресов:<a href="#" onclick="showRightAddressByLink(\'' . Html::e($link) . '\',0)">'
                . (int) ($row['addr_count'] ?? 0) . '</a><br>';
        }
        $body .= 'Предложений:<a href="#" onclick="showRightRealtyByLink(\'' . Html::e($link) . '\',0)">'
            . (int) ($row['realty_sell'] ?? 0) . '</a><br>'
            . 'Организаций:<a href="#" onclick="showRightOrgsByLink(\'' . Html::e($link) . '\',0)">'
            . (int) ($row['org_count'] ?? 0) . '</a>';
        $foto = $this->photosHtml($photos);
        if ($foto !== '') {
            $body .= '<br>' . $foto;
        }
        $body .= '</p>';
        return [$header, $body];
    }

    /** @param list<string> $urls */
    public function photosHtml(array $urls): string
    {
        $html = '';
        foreach ($urls as $url) {
            $file = basename(str_replace('\\', '/', (string) $url));
            if ($file === '' || $file === '.' || $file === '..') {
                continue;
            }
            $html .= '<a target=_blank href="' . Html::hostUrl($this->photoHost, '/admin/images/addresses/' . $file)
                . '"><img src="' . Html::hostUrl($this->photoHost, '/admin/images/addresses/1_' . $file) . '" width=100></a>';
        }
        return $html;
    }

    public function sideLink(float $lat, float $lon, int $streetId, int $houseId, string $fallbackPhrase = ''): string
    {
        $a = Html::number($lat) . ',' . Html::number($lon);
        if ($houseId > 0) {
            return 'a=' . $a . '&s=' . $streetId . '&h=' . $houseId;
        }
        return 'a=' . $a . '&f=' . rawurlencode($fallbackPhrase);
    }
}
