sitepages = {}

local sitetitle = {}
local siteroute = {}
local menuicon = {}

local function setvars(uid,acv)
    return {
        user = auth.getuserinfo(uid),
        titles = sitetitle,
        routes = siteroute,
        micon = menuicon,
        menuactive = acv
    }
end

function sitepages.record(req)
    local userid = auth.usercheck(req)
    if userid ~= nil then

        return req:render(setvars(userid,'Запись'))
    end
    return req:redirect_to('/')
end

function sitepages.salons(req)
    local userid = auth.usercheck(req)
    if userid ~= nil then
                
        return req:render(setvars(userid,'Салоны'))
    end
    return req:redirect_to('/')
end

function sitepages.services(req)
    local userid = auth.usercheck(req)
    if userid ~= nil then
                
        return req:render(setvars(userid,'Услуги'))
    end
    return req:redirect_to('/')
end

function sitepages.workers(req)
    local userid = auth.usercheck(req)
    if userid ~= nil then
                
        return req:render(setvars(userid,'Сотрудники'))
    end
    return req:redirect_to('/')
end

function sitepages.customers(req)
    local userid = auth.usercheck(req)
    if userid ~= nil then
                
        return req:render(setvars(userid,'Клиенты'))
    end
    return req:redirect_to('/')
end

function sitepages.quality(req)
    local userid = auth.usercheck(req)
    if userid ~= nil then
                
        return req:render(setvars(userid,'Качество'))
    end
    return req:redirect_to('/')
end

function sitepages.reports(req)
    local userid = auth.usercheck(req)
    if userid ~= nil then
                
        return req:render(setvars(userid,'Отчеты'))
    end
    return req:redirect_to('/')
end

function sitepages.сhangelog(req)
    local userid = auth.usercheck(req)
    if userid ~= nil then
                
        return req:render(setvars(userid,'Changelog'))
    end
    return req:redirect_to('/')
end

function sitepages.reference(req)
    local userid = auth.usercheck(req)
    if userid ~= nil then
                
        return req:render(setvars(userid,'Справка'))
    end
    return req:redirect_to('/')
end

function sitepages.settings(req)
    local userid = auth.usercheck(req)
    if userid ~= nil then
                
        return req:render(setvars(userid,'Настройки'))
    end
    return req:redirect_to('/')
end

function sitepages.start()
    sitetitle[1] = 'Запись'
    siteroute[1] = 'record'
    menuicon[1] = 'fa-tasks'
    sitetitle[2] = 'Салоны'
    siteroute[2] = 'salons'
    menuicon[2] = 'fa-institution'
    sitetitle[3] = 'Услуги'
    siteroute[3] = 'services'
    menuicon[3] = 'fa-gift'
    sitetitle[4] = 'Сотрудники'
    siteroute[4] = 'workers'
    menuicon[4] = 'fa-user'
    sitetitle[5] = 'Клиенты'
    siteroute[5] = 'customers'
    menuicon[5] = 'fa-users'
    sitetitle[6] = 'Качество'
    siteroute[6] = 'quality'
    menuicon[6] = 'fa-thumbs-down'
    sitetitle[7] = 'Отчеты'
    siteroute[7] = 'reports'
    menuicon[7] = 'fa-bar-chart'
    sitetitle[8] = 'Changelog'
    siteroute[8] = 'сhangelog'
    menuicon[8] = 'fa-code-fork'
    sitetitle[9] = 'Справка'
    siteroute[9] = 'reference'
    menuicon[9] = 'fa-book'
    sitetitle[10] = 'Настройки'
    siteroute[10] = 'settings'
    menuicon[10] = 'fa-gear'
end

function sitepages.stop()
    sitetitle = nil
    siteroute = nil
end

return sitepages