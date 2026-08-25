# Карта Томска

Городской справочник на карте: поиск адресов, организаций, объявлений о недвижимости и остановок общественного транспорта.

Слева — списки результатов, справа — Яндекс.Карта. Клик по точке показывает ближайшие объекты из своей базы. Геокодинг — через Яндекс, OpenStreetMap и 2GIS.

PHP 8 + MySQL, jQuery, Яндекс.Карты 2.0. Основной интерфейс — `index.php`, карта во фрейме — `map_frame.php`, админка координат — `admin_map.php` (`map.php` ведёт туда же). Скрипты карт и CDN подключаются по HTTPS; ключ Яндекса из `YANDEX_API_KEY` попадает в API, если это не заглушка `API_KEY`.

## Архитектура поиска

Списки слева и автокомплит идут через классы в `src/`:

- `Map\Domain\SearchQuery` / `QueryTokenizer` — разбор запроса
- `Map\Search\SearchService` — сценарий поиска
- `Map\Port\*` — интерфейсы репозиториев
- `Map\Infra\Mysqli*` — доступ к БД с prepared statements
- `ajax_result_*.php` — тонкие HTTP-входы

Админка по умолчанию открыта (`MAP_ALLOW_ADMIN`); в проде задайте `MAP_ADMIN_TOKEN` или выключите флаг.

## Индекс поиска и геокодер

`search_decomposer.php` и `cron_search_decomposer.php` — CLI-обёртки над `Map\Search\IndexBuilder`. Только из командной строки (из браузера — 403).

```bash
php search_decomposer.php              # индекс + координаты домов на организации
php search_decomposer.php --geocode    # то же, плюс Яндекс → OSM → 2GIS для дыр
php search_decomposer.php --no-coords  # только индекс, без UPDATE base_org
```

HTTP к геокодерам идёт только с `--geocode`. Без флага стоит `NullGeocoder`. Цепочка: `YandexGeocoder` → `OsmGeocoder` → `TwoGisGeocoder` (`Map\Geo\ChainGeocoder`).

## Карта

Слои и клик по точке:

- `Map\Map\MapService` — улицы, маршруты, пины, баннеры
- `Map\Map\ClickLookupService` — JSON для балуна
- `Map\Map\YandexMapView` — HTML/JS Яндекса из JSON-состояния
- `map_frame.php` / `admin_map.php` / `ajax_request.php` — тонкие входы

