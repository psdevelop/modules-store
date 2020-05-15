FROM ubuntu:18.04

RUN apt-get -y update \
&& apt-get -y upgrade \
&& apt-get install -y npm mc wget node \
&& npm uninstall vue-cli -g \
&& npm install -g @vue/cli

#cd code && vue create hello-world && cd hello-world
#grep -r "'8080" /code/ && npm run serve && vue-cli-service serve --port 8086
#npm run serve && vue-cli-service serve --port 8086 && npm install vue-cli-service
#npm vue-cli-service serve && ./node_modules/.bin/vue-cli-service serve --port 8086
#npm install -g serve && serve -s dist && npm run build

#CMD [ "/bin/bash", "top"]