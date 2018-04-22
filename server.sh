#!/bin/bash
ps -ef|grep 'master_process' |awk {'print $2'} | xargs kill -SIGUSR1
