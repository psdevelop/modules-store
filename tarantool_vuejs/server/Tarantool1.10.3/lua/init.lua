box.cfg {
    memtx_min_tuple_size = 16, -- Минимальный размер кортежей
    memtx_max_tuple_size = 1024 * 2, -- Максимальный размер кортежей
    memtx_memory = 512 * 1024 * 1024, -- 0,5 гига
}

-- Включаем фиберы
fiber = require('fiber')
json = require('json') 
crypto = require('crypto')
sitelib = require('sitelib')
apiauth = require('api.auth')
sitepages = require('sitepages')

-- Создаёт таблички при первом запуске
box.once(
	"tables_create",
    function()
        local tables = require("tables")
        tables:create() -- Создаем таблицы
        package.loaded['tables'] = nil
        -- Добавляем себя ))b2c84c2fa862d7fa5cd223505c7ef4d73cf9f294f0c0d500f9e0a04c73809fc6
        box.space.crm_user:insert({nil,'ecec82c85aebea57c61361143c1b91cc65aa0dc88b46c5a73853ac4e5d4e2cfd','Туричев Кирилл Викторович',1})
        box.space.crm_user:insert({nil,'d2a4c5ba743d49f17aad5f2a010b68f301733a5eac52cebfe742ccc22df0e977','Полтароков Станислав Петрович',1})
    end
)

-- Переменные окружения
SESSION_TIME = 60 * 60 * 3 -- 3 часа
CRM_VERSION = "0.3.4"

function restartsite()
    package.loaded['routes'] = nil
    routes = require("routes")
    routes.load(serverCMS)
end

-- Стартуем внутренний сервер
serverCMS = require('http.server').new('0.0.0.0', 3080, { 
    log_requests = true, display_errors = true 
})
serverCMS:start()
restartsite()
