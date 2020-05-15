FROM tarantool/tarantool:1.10.3

RUN apk update --no-cache \ 
 && apk upgrade \
 && apk add --no-cache curl jq

CMD [ "tarantool", "init.lua" ]