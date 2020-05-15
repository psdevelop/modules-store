routes = {}
function routes.load(server)
    -- Очистка старых маршрутов
    apiauth:stop()
    package.loaded['api.auth'] = nil    
    if server.routes ~= nil then
        for i, val in pairs(server.routes) do
            server.routes[i] = nil
        end
    end
    -- Очистка страниц
    sitepages:stop()
    package.loaded['sitepages'] = nil
    -- Очистка SiteLib
    package.loaded['sitelib'] = nil
    -- Очистка Api
    package.loaded['api.workers'] = nil
    -- Загрузка модулей    
    sitelib = require("sitelib")
    apiauth = require("api.auth")
    sitepages = require("sitepages")
    apiworkers = require('api.workers')
    -- Загрузка маршрутов
    apiauth:start()
    sitepages:start()
    --local crmtemplate = sitelib.readfile('templates/crmtemplate.html')
    server:route({ path = '/api/login', method = 'GET' }, apiauth.login)
    server:route({ path = '/api/logout', method = 'GET' }, apiauth.logout)
    server:route({ path = '/', method = 'GET', file = 'auth.html' }, apiauth.root)
    -- Api Сотрудников
    server:route({ path = '/api/workers/getusers', method = 'GET' }, apiworkers.getusers)
    -- Страницы
    --server:route({ path = '/record', method = 'GET', template = sitelib.glue(crmtemplate,'templates/record.html') }, sitepages.record)
    --server:route({ path = '/salons', method = 'GET', template = sitelib.glue(crmtemplate,'templates/salons.html') }, sitepages.salons)
    --server:route({ path = '/services', method = 'GET', template = sitelib.glue(crmtemplate,'templates/services.html') }, sitepages.services)
    --server:route({ path = '/workers', method = 'GET', template = sitelib.glue(crmtemplate,'templates/workers.html') }, sitepages.workers)
    --server:route({ path = '/customers', method = 'GET', template = sitelib.glue(crmtemplate,'templates/customers.html') }, sitepages.customers)
    --server:route({ path = '/quality', method = 'GET', template = sitelib.glue(crmtemplate,'templates/quality.html') }, sitepages.quality)
    --server:route({ path = '/reports', method = 'GET', template = sitelib.glue(crmtemplate,'templates/reports.html') }, sitepages.reports)
    --server:route({ path = '/сhangelog', method = 'GET', template = sitelib.glue(crmtemplate,'templates/сhangelog.html') }, sitepages.сhangelog)
    --server:route({ path = '/reference', method = 'GET', template = sitelib.glue(crmtemplate,'templates/reference.html') }, sitepages.reference)
    --server:route({ path = '/settings', method = 'GET', template = sitelib.glue(crmtemplate,'templates/settings.html') }, sitepages.settings)
    --crmtemplate = nil
end
return routes