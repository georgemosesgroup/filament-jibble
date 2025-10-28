<?php

return [
    'navigation' => [
        'groups' => [
            'integrations' => 'Интеграции',
        ],
    ],

    'common' => [
        'all' => 'Все',
        'labels' => [
            'connection' => 'Подключение',
            'status' => 'Статус',
            'tenant' => 'Тенант',
            'created' => 'Создано',
            'updated' => 'Обновлено',
            'person' => 'Сотрудник',
            'location' => 'Локация',
        ],
        'actions' => [
            'copy' => 'Скопировать',
        ],
        'placeholders' => [
            'not_available' => '—',
        ],
        'filters' => [
            'date_range' => [
                'label' => 'Диапазон дат',
                'from' => 'С',
                'until' => 'По',
                'indicator' => [
                    'range' => 'С :from по :until',
                    'from' => 'Начиная с :from',
                    'until' => 'До :until',
                ],
            ],
        ],
        'booleans' => [
            'with' => 'С',
            'without' => 'Без',
            'only_inside' => 'Только внутри',
            'only_outside' => 'Только вне',
        ],
    ],

    'connections' => [
        'label' => 'Подключение Jibble',
        'plural' => 'Подключения Jibble',
        'navigation_label' => 'Подключения Jibble',
        'form' => [
            'sections' => [
                'details' => [
                    'title' => 'Данные подключения',
                ],
                'settings' => [
                    'title' => 'Настройки',
                ],
            ],
            'fields' => [
                'name' => 'Название',
                'organization_uuid' => 'UUID организации',
                'client_id' => 'Client ID',
                'client_secret' => 'Client Secret',
                'api_token' => 'Персональный токен доступа',
                'settings' => [
                    'label' => 'Параметры',
                    'key' => 'Ключ',
                    'value' => 'Значение',
                    'add' => 'Добавить параметр',
                ],
            ],
        ],
        'table' => [
            'columns' => [
                'name' => 'Название',
                'organization_uuid' => 'Орг. UUID',
                'created_at' => 'Создано',
                'updated_at' => 'Обновлено',
            ],
            'filters' => [
                'tenant' => [
                    'label' => 'Тенант',
                ],
                'organization' => [
                    'label' => 'Организация',
                ],
                'credentials' => [
                    'label' => 'Учётные данные',
                    'with' => 'С учётными данными',
                    'without' => 'Без учётных данных',
                ],
            ],
            'actions' => [
                'sync' => 'Синхронизировать',
                'sync_all' => 'Синхронизировать все',
            ],
        ],
        'notifications' => [
            'sync_started' => [
                'title' => 'Синхронизация запущена',
                'all' => 'Запущены задачи синхронизации для всех подключений.',
                'single' => 'Запущены задачи синхронизации для подключения :name.',
            ],
        ],
    ],

    'people' => [
        'label' => 'Сотрудник Jibble',
        'plural' => 'Сотрудники Jibble',
        'navigation_label' => 'Сотрудники Jibble',
        'table' => [
            'columns' => [
                'full_name' => 'ФИО',
                'email' => 'Email',
                'connection' => 'Подключение',
                'status' => 'Статус',
                'created_at' => 'Создано',
            ],
            'filters' => [
                'status' => [
                    'label' => 'Статус',
                ],
                'connection' => [
                    'label' => 'Подключение',
                ],
                'email' => [
                    'label' => 'Email',
                    'with' => 'С email',
                    'without' => 'Без email',
                ],
            ],
        ],
    ],

    'locations' => [
        'label' => 'Локация Jibble',
        'plural' => 'Локации Jibble',
        'navigation_label' => 'Локации Jibble',
        'tabs' => [
            'main' => 'Локация Jibble',
            'details' => 'Детали',
            'geo' => 'Гео',
            'payload' => 'Payload',
        ],
        'sections' => [
            'general' => [
                'title' => 'Общее',
                'description' => 'Базовая информация о локации',
            ],
            'meta' => 'Метаданные',
        ],
        'fieldsets' => [
            'coordinates' => 'Координаты',
            'geofence' => 'Геозона',
        ],
        'fields' => [
            'name' => 'Название',
            'code' => 'Код',
            'status' => 'Статус',
            'status_hint' => 'Статус синхронизирован из Jibble.',
            'address' => 'Адрес',
            'connection' => 'Подключение',
            'latitude' => 'Широта',
            'longitude' => 'Долгота',
            'radius' => 'Радиус',
            'units' => 'Единицы',
            'payload' => 'Исходный JSON (только чтение)',
            'payload_hint' => 'Исходный ответ Jibble для отладки.',
            'created_at' => 'Создано',
            'updated_at' => 'Импортировано',
            'tenant' => 'Тенант',
        ],
        'table' => [
            'columns' => [
                'code' => 'Код',
                'connection' => 'Подключение',
                'address' => 'Адрес',
                'latitude' => 'Широта',
                'longitude' => 'Долгота',
                'radius' => 'Радиус (м)',
                'imported' => 'Импортировано',
            ],
            'filters' => [
                'status' => [
                    'label' => 'Статус',
                ],
                'connection' => [
                    'label' => 'Подключение',
                ],
                'coordinates' => [
                    'label' => 'Координаты',
                    'with' => 'С координатами',
                    'without' => 'Без координат',
                ],
            ],
        ],
    ],

    'sync_logs' => [
        'label' => 'Лог синхронизации',
        'plural' => 'Логи синхронизации',
        'navigation_label' => 'Логи синхронизации',
        'table' => [
            'columns' => [
                'queued' => 'В очереди',
                'resource' => 'Ресурс',
                'status' => 'Статус',
                'connection' => 'Подключение',
                'message' => 'Сообщение',
                'started_at' => 'Начато',
                'finished_at' => 'Завершено',
            ],
            'filters' => [
                'status' => [
                    'label' => 'Статус',
                    'options' => [
                        'running' => 'В работе',
                        'failed' => 'Ошибка',
                        'completed' => 'Готово',
                    ],
                ],
                'resource' => [
                    'label' => 'Ресурс',
                    'options' => [
                        'people' => 'Сотрудники',
                        'timesheets' => 'Табели',
                        'timesheets_summary' => 'Сводка табелей',
                        'time_entries' => 'Отметки времени',
                    ],
                ],
                'connection' => [
                    'label' => 'Подключение',
                ],
                'queued_between' => [
                    'label' => 'В очереди в период',
                ],
            ],
        ],
    ],

    'time_entries' => [
        'label' => 'Отметка времени Jibble',
        'plural' => 'Отметки времени Jibble',
        'navigation_label' => 'Отметки времени Jibble',
        'table' => [
            'columns' => [
                'picture' => 'Фото',
                'date' => 'Дата',
                'type' => 'Тип',
                'status' => 'Статус',
                'person' => 'Сотрудник',
                'connection' => 'Подключение',
                'time' => 'Время (UTC)',
                'local_time' => 'Локальное время',
                'location' => 'Локация',
                'client' => 'Клиент',
                'project' => 'Проект',
                'activity' => 'Активность',
                'location_id' => 'ID локации',
                'note' => 'Комментарий',
                'outside_geofence' => 'Вне геозоны',
            ],
            'filters' => [
                'date_range' => [
                    'label' => 'Диапазон дат',
                ],
                'connection' => [
                    'label' => 'Подключение',
                ],
                'person' => [
                    'label' => 'Сотрудник',
                ],
                'location' => [
                    'label' => 'Локация',
                ],
                'status' => [
                    'label' => 'Статус',
                ],
                'type' => [
                    'label' => 'Тип',
                ],
                'outside_geofence' => [
                    'label' => 'Вне геозоны',
                    'true' => 'Только вне',
                    'false' => 'Только внутри',
                ],
                'picture' => [
                    'label' => 'Фото',
                    'with' => 'С фото',
                    'without' => 'Без фото',
                ],
            ],
        ],
    ],

    'timesheets' => [
        'label' => 'Табель Jibble',
        'plural' => 'Табели Jibble',
        'navigation_label' => 'Табели Jibble',
        'table' => [
            'columns' => [
                'date' => 'Дата',
                'status' => 'Статус',
                'person' => 'Сотрудник',
                'connection' => 'Подключение',
                'tracked_seconds' => 'Учтено (с)',
                'billable_seconds' => 'Оплачиваемо (с)',
                'break_seconds' => 'Перерыв (с)',
            ],
            'filters' => [
                'date_range' => [
                    'label' => 'Диапазон дат',
                ],
                'status' => [
                    'label' => 'Статус',
                ],
                'connection' => [
                    'label' => 'Подключение',
                ],
                'person' => [
                    'label' => 'Сотрудник',
                ],
            ],
        ],
    ],

    'timesheet_summaries' => [
        'label' => 'Сводка табеля',
        'plural' => 'Сводки табелей',
        'navigation_label' => 'Сводки табелей',
        'table' => [
            'columns' => [
                'person' => 'Сотрудник',
                'connection' => 'Подключение',
                'period' => 'Период',
                'tracked' => 'Учтено',
                'billable' => 'Оплачиваемо',
                'breaks' => 'Перерывы',
                'updated' => 'Обновлено',
            ],
            'filters' => [
                'date_range' => [
                    'label' => 'Диапазон дат',
                ],
                'connection' => [
                    'label' => 'Подключение',
                ],
                'person' => [
                    'label' => 'Сотрудник',
                ],
            ],
        ],
    ],

    'plugin' => [
        'menu' => [
            'tenant_settings' => 'Интеграция Jibble',
            'profile_settings' => 'Настройки Jibble',
        ],
    ],

    'pages' => [
        'profile' => [
            'navigation_label' => 'Настройки Jibble',
            'title' => 'Интеграция Jibble',
            'form' => [
                'fields' => [
                    'name' => 'Название подключения',
                    'organization' => 'Организация',
                    'client_id' => 'Client ID',
                    'client_secret' => 'Client Secret',
                    'api_token' => 'Персональный токен доступа',
                ],
                'placeholders' => [
                    'organization' => 'Загрузить организации из Jibble',
                ],
                'helpers' => [
                    'organization' => 'Сохраните учётные данные и загрузите организации через API.',
                ],
                'actions' => [
                    'fetch' => 'Загрузить из Jibble',
                ],
            ],
            'notifications' => [
                'no_connection' => [
                    'title' => 'Подключение не сохранено',
                    'body' => 'Сначала сохраните учётные данные Jibble, чтобы запустить синхронизацию.',
                ],
                'sync_started' => [
                    'title' => 'Синхронизация запущена',
                    'body' => 'Задачи синхронизации запущены для вашего подключения Jibble.',
                ],
                'sync_failed' => [
                    'title' => 'Не удалось запустить синхронизацию',
                ],
                'saved' => [
                    'title' => 'Подключение Jibble сохранено',
                ],
                'save_failed' => [
                    'title' => 'Не удалось сохранить подключение',
                ],
                'verified' => [
                    'title' => 'Подключение проверено',
                    'body' => 'Связь с API Jibble установлена.',
                ],
                'verify_failed' => [
                    'title' => 'Не удалось проверить подключение',
                ],
                'save_first' => [
                    'title' => 'Сначала сохраните учётные данные',
                    'body' => 'Сохраните данные подключения перед загрузкой организаций.',
                ],
                'no_organizations' => [
                    'title' => 'Организации не найдены',
                    'body' => 'API Jibble вернул пустой список организаций.',
                ],
                'organizations_loaded' => [
                    'title' => 'Организации загружены',
                    'body' => 'Выберите организацию в списке перед сохранением.',
                ],
                'organizations_failed' => [
                    'title' => 'Не удалось загрузить организации',
                ],
            ],
        ],
        'tenant' => [
            'navigation_label' => 'Интеграция Jibble',
            'title' => 'Настройки Jibble для тенанта',
            'form' => [
                'fields' => [
                    'name' => 'Название подключения',
                    'organization' => 'Организация',
                    'client_id' => 'Client ID',
                    'client_secret' => 'Client secret',
                    'api_token' => 'Персональный токен доступа',
                ],
                'placeholders' => [
                    'organization' => 'Выберите организацию',
                ],
                'helpers' => [
                    'name' => 'Короткое имя подключения (например, «primary»).',
                    'organization' => 'Загрузите список организаций по данным подключения и выберите нужную.',
                    'client_id' => 'Нужно, если вместо токена используется OAuth-клиент.',
                    'client_secret' => 'Нужно, если вместо токена используется OAuth-клиент.',
                    'api_token' => 'Если указать токен, поля Client ID/Secret можно оставить пустыми.',
                ],
                'actions' => [
                    'fetch_organizations' => 'Загрузить организации',
                ],
                'sections' => [
                    'connection' => 'Подключение',
                    'connection_desc' => 'Выберите организацию и базовые параметры для этого тенанта.',
                    'credentials' => 'Учетные данные',
                    'credentials_desc' => 'Выберите OAuth-клиент (Client ID/Secret) или персональный токен.',
                    'oauth' => 'OAuth-клиент',
                ],
            ],
            'notifications' => [
                'saved' => [
                    'title' => 'Настройки тенанта Jibble обновлены',
                ],
                'save_failed' => [
                    'title' => 'Не удалось обновить настройки',
                ],
                'verified' => [
                    'title' => 'Подключение проверено',
                    'body' => 'Успешно выполнен запрос к Jibble API.',
                ],
                'verify_failed' => [
                    'title' => 'Не удалось проверить подключение',
                ],
                'save_first' => [
                    'title' => 'Сначала сохраните подключение',
                    'body' => 'Сохраните учетные данные Jibble перед загрузкой организаций.',
                ],
                'no_organizations' => [
                    'title' => 'Организации не найдены',
                    'body' => 'API Jibble вернул пустой список организаций.',
                ],
                'organizations_loaded' => [
                    'title' => 'Организации загружены',
                    'body' => 'Выберите организацию в списке перед сохранением.',
                ],
                'organizations_failed' => [
                    'title' => 'Не удалось загрузить организации',
                ],
            ],
            'validation' => [
                'credentials' => 'Укажите персональный токен или пару Client ID / Client secret.',
            ],
            'profile' => [
                'page_title' => 'Настройки тенанта',
                'section_title' => 'Предпочтения Jibble',
                'fields' => [
                    'default_project' => 'Проект по умолчанию',
                    'default_group' => 'Группа по умолчанию',
                ],
                'placeholders' => [
                    'default_project' => 'Выберите проект',
                    'default_group' => 'Выберите группу',
                ],
                'helpers' => [
                    'default_project' => 'Укажите проект Jibble, который будет использоваться по умолчанию.',
                    'default_group' => 'Укажите группу Jibble, которая будет использоваться по умолчанию.',
                ],
                'actions' => [
                    'fetch_projects' => 'Загрузить проекты',
                    'fetch_groups' => 'Загрузить группы',
                ],
                'notifications' => [
                    'requires_connection' => [
                        'title' => 'Сначала добавьте учетные данные',
                        'body' => 'Сохраните подключение к Jibble для этого тенанта перед настройкой предпочтений.',
                    ],
                    'requires_organization' => [
                        'title' => 'Выберите организацию',
                        'body' => 'Выберите организацию на странице настроек Jibble перед загрузкой проектов или групп.',
                    ],
                    'no_projects' => [
                        'title' => 'Проекты не найдены',
                        'body' => 'API Jibble вернул пустой список проектов.',
                    ],
                    'no_groups' => [
                        'title' => 'Группы не найдены',
                        'body' => 'API Jibble вернул пустой список групп.',
                    ],
                    'projects_loaded' => [
                        'title' => 'Проекты загружены',
                        'body' => 'Выберите проект, который будет использоваться по умолчанию для этого тенанта.',
                    ],
                    'groups_loaded' => [
                        'title' => 'Группы загружены',
                        'body' => 'Выберите группу, которая будет использоваться по умолчанию для этого тенанта.',
                    ],
                    'projects_failed' => [
                        'title' => 'Не удалось загрузить проекты',
                    ],
                    'groups_failed' => [
                        'title' => 'Не удалось загрузить группы',
                    ],
                ],
            ],
        ],
        'api_explorer' => [
            'navigation_label' => 'Jibble API',
            'fieldsets' => [
                'request' => 'Запрос',
                'timesheet_filters' => 'Фильтры табелей',
                'timesheet_summary_filters' => 'Фильтры сводок табелей',
            ],
            'fields' => [
                'connection' => [
                    'label' => 'Подключение',
                    'helper' => 'Выберите учетные данные Jibble.',
                ],
                'resource' => 'Ресурс',
                'custom_endpoint' => [
                    'label' => 'Пользовательский endpoint',
                    'placeholder' => 'organizations/{organization}/custom-path',
                ],
                'http_method' => 'Метод',
                'paginate' => 'Использовать пагинацию',
                'identifier' => [
                    'label' => 'Идентификатор',
                    'helper' => 'Нужно для операций просмотра, обновления и удаления.',
                ],
                'organization_uuid' => [
                    'label' => 'UUID организации',
                    'helper' => 'Переопределяет организацию по умолчанию.',
                ],
                'replacements' => [
                    'label' => 'Подстановки пути',
                    'add' => 'Добавить подстановку',
                ],
                'query' => [
                    'label' => 'Параметры запроса',
                    'add' => 'Добавить параметр',
                ],
                'payload' => 'Тело запроса (JSON)',
                'date' => 'Дата',
                'period' => 'Период',
                'start_date' => 'Дата начала',
                'end_date' => 'Дата окончания',
                'person_ids' => 'ID сотрудников',
            ],
            'resources' => [
                'custom' => 'Пользовательский endpoint',
            ],
            'errors' => [
                'missing_endpoint' => 'Укажите путь пользовательского endpoint.',
                'unsupported_method' => 'Метод HTTP не поддерживается: :method',
                'missing_identifier_update' => 'Для обновления требуется идентификатор.',
                'missing_identifier_delete' => 'Для удаления требуется идентификатор.',
                'invalid_json' => 'Тело запроса должно быть корректным JSON: :error',
                'payload_not_object' => 'Декодированное тело должно быть JSON-объектом.',
            ],
        ],
    ],

    'widgets' => [
        'sync_status' => [
            'title' => 'Синхронизация Jibble',
            'empty' => [
                'value' => 'Синхронизаций еще не было',
                'description' => 'Запустите команду jibble:sync для импорта данных',
            ],
            'description' => [
                'finished' => 'Завершено :time',
                'started' => 'Запущено :time',
                'queued' => 'В очереди',
            ],
            'status' => [
                'completed' => 'Готово',
                'failed' => 'Ошибка',
                'running' => 'В работе',
                'default' => 'В очереди',
            ],
        ],
        'timesheet_heatmap' => [
            'employee' => 'Сотрудник',
            'total' => 'Итого',
            'month' => 'Месяц',
            'year' => 'Год',
            'search_placeholder' => 'Поиск по имени или email…',
            'search_empty' => 'Нет сотрудников, подходящих под фильтры.',
            'legend_title' => 'Легенда',
            'statuses' => [
                'missing' => 'Нет данных',
                'off' => 'Выходной / Отсутствует',
                'target' => 'В норме',
                'extended' => 'Продлённый день',
                'overtime' => 'Сверхурочно',
            ],
            'tooltip' => [
                'no_data' => 'Нет учтённого времени',
            ],
            'empty' => [
                'heading' => 'Нет данных по табелям',
                'body' => 'Мы не нашли синхронизированных табелей за выбранный месяц.',
            ],
            'no_branch' => [
                'heading' => 'Тенант не выбран',
                'body' => 'Выберите тенанта, чтобы увидеть тепловую карту его команды.',
            ],
        ],
    ],
];
