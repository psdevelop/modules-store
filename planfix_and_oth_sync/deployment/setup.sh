#!/bin/bash
#making script to stop on 1st error
set -e

usage="Usage: setup --help | --update-script | --tests | --install [-b cabinet-branch] | --checkout [-b cabinet-branch]"
phing="/usr/bin/phing"
options=""
target=""

if [ -z "$1" ]; then
    echo $usage
    exit 1
fi

if [ "$1" = '--help' ]; then
    echo $usage
    echo ""
    echo "  --help            Show this screen"
    echo "  --update-script   Update cabinet-setup.xml from master branch"
    echo "  --update-script-dev   Update cabinet-setup.xml from master branch on Dev server"
    echo "  -p                Set project path"
    echo "  --tests           Run tests"
    echo "  --install         Install cabinet project with full reinstall DB"
    echo "  --checkout        Install cabinet project without install DB, but using migrations"
    echo "  -b                Set branch for cabinet project to install or checkout"
    echo "                    (Defaults use cabinetBranch property in cabinet-setup.xml)"
    echo ""
    exit 0
fi

if [ "$1" = '--update-script' ]; then
    branch="master"
    if [ "$2" = "-b" ]; then
        if [ -z "$3" ]; then
            echo $usage
            exit 1
        fi
    branch=$3
    fi
    /usr/bin/git archive --remote="git@gitlab.leads.local:root/planfix.git" $branch:deployment setup.xml | tar -xO > /home/app/deployment/setup.xml

    exit 0
fi

if [ "$1" = '--update-script-dev' ]; then
    if [ "$2" = '-p' ]; then
        project = "$3"
    else
        exit 0
    fi

    branch="master"
    if [ "$4" = "-b" ]; then
        if [ -z "$5" ]; then
            echo $usage
            exit 1
        fi
    branch=$3
    fi
    /usr/bin/git archive --remote="git@gitlab.leads.local:root/planfix.git" $branch:deployment setup.xml | tar -xO > /home/app/$project/deployment/setup.xml

    exit 0
fi

if [ "$1" = '--tests' ]; then
    target="tests"
fi

if [ "$1" = '--checkout' ]; then
    target="checkout"
fi

if [ "$1" = '--install' ]; then
    target="install"
fi

if [ "$2" = "-b" ]; then
    if [ -z "$3" ]; then
        echo $usage
        exit 1
    fi
    options="-DcabinetBranch=$3"
fi

if [ "$4" != "-env" ]; then
    phing_script="-f /home/app/planfix/deployment/setup.xml"
    envConfig=production
else
    phing_script="-f /home/app/planfix/deployment/setup.xml"
    envConfig=production
    if [ "$5" = "dev" ]; then
        phing_script="-f /home/app/planfix/deployment/setup.xml"
        envConfig=development
    fi
fi

options+=" -DenvConfig=$envConfig"

if [ -n "$2" -a "$2" != "-b" ]; then
    echo $usage
    exit 1
fi

if [ "$target" = "" ]; then
    echo $usage
    exit 1
fi

script="$phing $options $phing_script $target"
eval $script

