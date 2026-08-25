<?php

namespace Map\Search;

use Map\Domain\Address;
use Map\Domain\Organization;
use Map\Domain\PagedResult;
use Map\Domain\RealtyListing;
use Map\Domain\SearchQuery;
use Map\Domain\TransitStop;
use Map\Infra\Html;
use Map\Port\ResultPresenter;

final class HtmlListPresenter implements ResultPresenter
{
    public function render(PagedResult $result, SearchKind $kind): string
    {
        return match ($result->error) {
            PagedResult::ERR_BAD_PARAMS => '<p class=small_orgs>Ошибка! Неверные параметры.</p>',
            PagedResult::ERR_INVALID_ADDRESS => '<p class=small_orgs>Ошибка! Неверный адрес.</p>',
            PagedResult::ERR_NOT_FOUND => '<p class=small_orgs>Ничего найдено не было</p>',
            default => $this->list($result, $kind),
        };
    }

    private function list(PagedResult $result, SearchKind $kind): string
    {
        if ($result->total === 0 && $result->items === []) {
            return '';
        }

        $html = '<p class=small_orgs_com>Всего совпадений: ' . (int) $result->total . '</p>';
        $html .= '<a onclick="$(\'#' . $kind->listId() . '\').toggle();" id="org_a" href="javascript:;">+</a>';
        $html .= "<br class='clear'>";
        $display = $result->query->expanded ? 'display:block' : 'display:none';
        $html .= '<div style="' . $display . '" id="' . $kind->listId() . '">';

        if ($result->total > 0) {
            $index = $result->startIndex();
            foreach ($result->items as $item) {
                $index++;
                $html .= $this->item($kind, $item, $result->query, $index);
            }
            $html .= $this->pager($result, $kind);
            $html .= '</div>';
        }

        return $html;
    }

    private function item(SearchKind $kind, object $item, SearchQuery $query, int $index): string
    {
        return match ($kind) {
            SearchKind::Organization => $item instanceof Organization
                ? $this->organization($item, $query, $index)
                : '',
            SearchKind::Realty => $item instanceof RealtyListing
                ? $this->realty($item, $query, $index)
                : '',
            SearchKind::Address => $item instanceof Address
                ? $this->address($item, $index)
                : '',
            SearchKind::Stop => $item instanceof TransitStop
                ? $this->stop($item, $index)
                : '',
        };
    }

    private function organization(Organization $org, SearchQuery $query, int $index): string
    {
        $html = '<p class=small_orgs><b>' . $index . '</b>. ' . Html::e($org->name);
        if ($org->address !== '') {
            $html .= '<br>' . Html::e($org->address);
        }
        if ($org->site !== '') {
            $href = Html::url($org->site);
            if ($href !== '') {
                $html .= '<br><a target=blank href="' . $href . '">' . Html::e($org->site) . '</a>';
            }
        }
        if ($org->email !== '') {
            $html .= '<br><a target=blank href="mailto:' . Html::e($org->email) . '">' . Html::e($org->email) . '</a>';
        }
        foreach ($org->phones as $phone) {
            $html .= '<br>тел.' . Html::e($phone);
        }
        if (!$query->houseId) {
            $html .= $this->mapLink($org->lat, $org->lon);
        }
        return $html . '</p>';
    }

    private function realty(RealtyListing $listing, SearchQuery $query, int $index): string
    {
        $plain = strip_tags($listing->description);
        $short = $query->hasPhrase() ? mb_substr($plain, 0, 100) : $plain;
        if ($query->hasPhrase() && mb_strlen($plain) > 100) {
            $short .= '...';
        }

        $html = '<p class=small_orgs><b>' . $index . '</b>. '
            . Html::e($listing->dealLabel()) . ' '
            . Html::e($listing->rooms) . '-комнатная '
            . Html::e($listing->typeName) . '<br>'
            . Html::e($short) . '<br>'
            . 'Цена:';
        if ($listing->price !== '') {
            $html .= '<b>' . Html::e($listing->price) . 'р.</b><br>';
        }
        $html .= 'Контакт:' . Html::e($listing->contactName) . ' ' . Html::e($listing->contacts) . '<br>';

        if ($query->hasPhrase()) {
            $html .= Html::e($listing->street) . ', ' . Html::e($listing->house) . '<br>';
            $html .= $this->districtLine($listing->district, $listing->massive);
        } else {
            $html .= Html::e($listing->registeredAt);
        }

        if (!$query->houseId) {
            $html .= $this->mapLink($listing->lat, $listing->lon, $query->hasPhrase() ? '' : null);
        }
        return $html . '</p>';
    }

    private function address(Address $address, int $index): string
    {
        $html = '<p class=small_orgs><b>' . $index . '.</b> Адрес: '
            . Html::e($address->street) . ', ' . Html::e($address->house) . '<br>';
        $html .= $this->districtLine($address->district, $address->massive);
        $html .= $this->mapLink($address->lat, $address->lon, '');
        return $html . '</p>';
    }

    private function stop(TransitStop $stop, int $index): string
    {
        $routes = [];
        foreach ($stop->routes as $code) {
            $label = str_replace(['А', 'Т', 'М'], ['Автобус №', 'Троллейбус №', 'Марштурка №'], $code);
            $routes[] = '<a onclick="showRoute(\'' . Html::e($code) . '\')" href=# style="white-space:nowrap;">'
                . Html::e($label) . '</a>';
        }
        $extra = 'bs=' . Html::e($stop->fid);
        $html = '<p class=small_orgs><b>' . $index . '.</b> Название: ' . Html::e($stop->name) . '<br>'
            . 'Маршруты: ' . implode(', ', $routes) . ' ';
        $html .= $this->mapLink($stop->lat, $stop->lon, $extra);
        return $html . '</p>';
    }

    private function districtLine(string $district, string $massive): string
    {
        if ($massive !== $district) {
            $html = '';
            if ($district !== '') {
                $html .= 'Район: ' . Html::e($district) . '<br>';
            }
            if ($massive !== '') {
                $html .= 'Микрорайон: ' . Html::e($massive) . '<br>';
            }
            return $html;
        }
        if ($district === '') {
            return '';
        }
        return Html::e($district) . '<br>';
    }

    private function mapLink(?float $lat, ?float $lon, ?string $extra = null): string
    {
        $prefix = $extra === '' ? '' : '<br>';
        $args = Html::number($lat) . ',' . Html::number($lon);
        if ($extra) {
            $args .= ',\'' . $extra . '\'';
        }
        return $prefix . '<a onclick="showObject(' . $args . ')" href="javascript:;">посмотреть на карте</a>';
    }

    private function pager(PagedResult $result, SearchKind $kind): string
    {
        $query = $result->query;
        $fn = $kind->jsLoader();
        $linkArg = $this->pagerLink($kind, $query);
        $html = '<p class=paginator>';
        if ($result->page > 0) {
            $html .= '<a href="javascript:;" onclick="' . $fn . '(' . $linkArg . ',' . ($result->page - 1) . ',1)">'
                . '<img src="/images/black_arrow_left.png" onmouseover="$(this).attr(\'src\', \'/images/red_arrow_left.png\');" onmouseout="$(this).attr(\'src\', \'/images/black_arrow_left.png\');" class="left_arrow"></a>';
        }
        if ($result->page < $result->maxPage()) {
            $html .= '<a href="javascript:;" onclick="' . $fn . '(' . $linkArg . ',' . ($result->page + 1) . ',1)">'
                . '<img src="/images/black_arrow.png" onmouseover="$(this).attr(\'src\', \'/images/red_arrow.png\');" onmouseout="$(this).attr(\'src\', \'/images/black_arrow.png\');" class="right_arrow"></a>';
        }
        return $html . ' ';
    }

    private function pagerLink(SearchKind $kind, SearchQuery $query): string
    {
        if (!$query->hasPhrase() && ($kind === SearchKind::Realty || $kind === SearchKind::Address)) {
            return 'location.search.slice(1)';
        }
        if (!$query->hasPhrase() && $kind === SearchKind::Organization) {
            $link = 'm=' . $query->massiveId
                . '&d=' . $query->districtId
                . '&h=' . $query->houseId
                . '&s=' . $query->streetId
                . '&a=' . ($query->point?->asQuery() ?? '')
                . '&f=' . rawurlencode($query->rawPhrase);
            return '\'' . $link . '\'';
        }
        $a = $query->point?->asQuery() ?? '';
        return '\'a=' . $a . '&f=' . rawurlencode($query->rawPhrase) . '\'';
    }
}
