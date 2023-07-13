
## Ubuntu 14.04 Test Environment

This docker image contains everything needed to test boost-tasks in an offline environment. Please read all the following instructions.

Acquire a github token at https://github.com/settings/tokens . The token doesn't need extra permissions. Standard read-only is preferable.

Add it to your environment:
```
export GITHUBTOKEN=_the_token_
```

Build the Dockerfile:

```
./build.sh
```

Start the container: 
```
docker run -d _name_
```

Connect to the container:
```
docker exec -it _name_ bash
```

Adjust the /etc/hosts file so all traffic is redirected to be internal. If you don't make this change, then you'll still interact with the internet, instead of only local testing.
```
cat /opt/github/boostorg/boost-tasks/tests/dockers/ubuntu-14.04/hosts >> /etc/hosts
```

Start additional required processes:
```
service ssh start
service apache2 start
```

Run the various boost-tasks scripts:
```
cd /opt/github/boostorg/boost-tasks/
./mirror  # etc.
```

