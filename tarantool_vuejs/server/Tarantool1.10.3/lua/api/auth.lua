auth = {}

-- Необходимо усилить защиту и генерировать hash заголовков браузера
-- сверяя все время с тем который был при авторизации.
-- Таким образом защитимся от угона куки.
function auth.usercheck(req)
    local result = nil
    local token = req:cookie('token')

    if (token == nil) or (token:len() ~= 32) then
        return result 
    end  

    local session_id = box.space.crm_session.index.i_s_token:select(token)
    if session_id[1] ~= nil then
        box.space.crm_session:update({
            session_id[1][1]
        },
        {
            {'=', 3, os.time() + SESSION_TIME}
        })
        result = session_id[1][4]
    end

    return result
end

function auth.getuserinfo(userid)
    -- Поставить проверку на число
    local userres = box.space.crm_user.index.i_u_id:select({userid})
    if userres[1] ~= nil then
        return {
            id = userres[1][1],
            fio = userres[1][3],
            role = userres[1][4]
        }
    else
        return nil
    end
end

function auth.root(req)
    if auth.usercheck(req)  ~= nil then
        return req:redirect_to('/record')
    else
        return req:render()
    end
end

function auth.logout(req)
    local token = req:cookie('token')
    if (token == nil) or (token:len() ~= 32) then
        return req:redirect_to('/') 
    end     

    local result = box.space.crm_session.index.i_s_token:select(token)
    if result[1] ~= nil then
        box.space.crm_session:delete({result[1][1]})
    end

    return req:redirect_to('/')
end

function auth.get_token(length)
    local chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
    local randomString = ''

    math.randomseed(os.time())

    charTable = {}
    for c in chars:gmatch"." do
        table.insert(charTable, c)
    end

    for i = 1, length do
        randomString = randomString .. charTable[math.random(1, #charTable)]
    end
    return randomString
end

function auth.login(req)
    local result = {status = false}
    local pass_hash = req:query_param('hash')
    local token = req:cookie('token')

    if (token == nil) then
        token = ''
    end
    token = tostring(token)

    if (token:len() == 32) then
        -- Проверяем на существование сессии с переданным токеном
        local session_result = box.space.crm_session
            .index.i_s_token:select(token)
        if session_result[1] ~= nil then
            result.status = true
            result.session_id = token
            return sitelib.httptypejson(200, result)
        end
    end

    if (pass_hash == nil) or (pass_hash:len() ~= 64) then
        return sitelib.httptypejson(401, result)
    end

    pass_hash = pass_hash:lower()
    -- Проверяем что число 16-тиричное
    if (string.match(pass_hash,'%x+') ~= pass_hash) then
        return sitelib.httptypejson(401, result)
    end

    -- Проверяем на существование пользователя
    local user_result = box.space.crm_user.index.i_u_logpasshash:select(pass_hash)
    if user_result[1] == nil then
        return sitelib.httptypejson(401, result)
    end

    -- Добавляем сессию
    token = auth.get_token(32)
    box.space.crm_session:insert({
        nil,
        token,
        os.time() + SESSION_TIME,
        user_result[1][1],
        req.headers
    })
    result.token = token
    result.status = true

    return sitelib.httptypejson(200, result)
end

function auth.stop()
    -- Очистка страниц

    -- Остановка файбера сесий
    if SESSION_FIBER ~= nil then
        SESSION_FIBER:cancel()
        SESSION_FIBER = nil
    end
    return
end

local function sessionfiber()
    while true do
        expires_session = box.space.crm_session.index.i_s_time:select(os.time(),{iterator = 'LT'})
        for i, val in pairs(expires_session) do
            box.space.crm_session.index.i_s_id:delete({expires_session[i][1]})
        end
        fiber.sleep(1000)
    end
end

function auth.start()
    -- Загрузка страниц

    -- Запуск файбера сессий
    SESSION_FIBER = fiber.create(sessionfiber)
    return
end

return auth
--box.schema.func.create('check_access', {setuid= true})
--box.schema.user.grant('public_user', 'execute', 'function', 'check_access')
