#!/bin/bash

if [ -z "$GITHUBTOKEN" ]; then
  echo "Please set the github token first."
  echo "export GITHUBTOKEN=__"
  exit 1
fi

docker build --tag boost-tasks-1804 --build-arg GITHUBTOKEN=$GITHUBTOKEN .
