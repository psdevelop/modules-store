workers = {}

function workers.getusers(req)
    local __ostime = os.clock()
    local userid = auth.usercheck(req)
    local __page = tonumber(req:query_param('page'))
    local result = {}
    result.status = false
    if (userid ~= nil) and (__page ~= nil) and (__page > 0) then
        -- 10 строк на лист
        local __limit = 10
        result.count = box.space.crm_user:count()
        local startpage = (__page - 1) * __limit
        if result.count > startpage then
            local i = 1
            result.users = {}
            for _, val in box.space.crm_user.index.i_u_id:pairs(startpage + 1,{iterator=box.index.GE}) do
                if i > __limit then break end
                local roles = {'Администратор', 'Пользователь'}
                result.users[i] = {
                    id = val[1],
                    fio = val[3],
                    role = roles[val[4]]
                }
                i = i + 1
                fiber.yield()
            end
            result.pagecount = math.floor(result.count/__limit)
            if (math.fmod(result.count,__limit) > 0) then result.pagecount = result.pagecount + 1 end
            result.status = true
            --fiber.sleep(1)
            result.time = (os.clock() - __ostime) * 1000
            return sitelib.httptypejson(200,result)
        end
        result.count = nil
    end
    return sitelib.httptypejson(200,result)
end

return workers