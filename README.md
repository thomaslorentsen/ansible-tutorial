# Introduction to Ansible
The Ansible website describes it as:
> Ansible is a simple automation language that can perfectly describe an IT application infrastructure

Ansible runs on the client side so there is nothing to install on the systems being managed.
Ansible only needs SSH access to the machines that you are managing.
## Prerequisites
We will need to [install Ansible](http://docs.ansible.com/ansible/intro_installation.html) on your machine to use it.

### Installing Ansible on OSX
First install pip if you have not already installed it
```bash
sudo easy_install pip
```
Then install the latest version of Ansible
```bash
sudo pip install ansible
```
### Clone This Repository
If you want to try these examples for yourself, then clone the repository:
```bash
git clone https://github.com/thomaslorentsen/ansible-tutorial.git
cd ansible-tutorial
```
### Start Example Vagrant Boxes
To try the examples of this tutorial please start the vagrant boxes:
```bash
vagrant up
```
When we are done you can destroy the vagrant boxes with
```bash
vagrant destroy
```
# Inventory
An inventory describes all your servers in your infrastructure.
## Creating an inventory
An inventory is in the ```ini``` format which contains a heading to group a list of servers and then their host names:
```ini
[frontend]
frontend-server-1 ansible_host=192.168.0.32
frontend-server-2 ansible_host=192.168.0.33
frontend-server-3 ansible_host=192.168.0.34

[backend]
backend-server-1 ansible_host=192.168.33.34 ansible_user=vagrant 
backend-server-2 ansible_host=192.168.33.35 ansible_user=vagrant

[docker]
docker-1 ansible_host=192.168.33.36
```
# Demo Background
In this demo we are going to do what most developers do and build a website for our cat
# Playbooks
A playbook is Ansibles scripting language for orchestrating and configuring servers.
A playbook is in the ```yaml``` format making it easy to read and understand.
### Running A Playbook
We can install a webserver with a simple playbook.
```yaml
- hosts: frontend-1
  become: true
  tasks:
    - name: Install Web Server
      yum:
        name: httpd
        state: present
    - name: Start Web Server
      service:
        name: httpd
        state: started
```
Pearl wants a webserver installed

You can run this with:
```bash
ansible-playbook \
    -i inventory/inventory.ini \
    install_http_server.yml
```
When you run the playbook you will see that it runs all the tasks in the playbook.
After all the tasks have been completed it will print out a summary of the results.
```
PLAY [frontend-1] **************************************************************

TASK [setup] *******************************************************************
ok: [frontend-1]

TASK [Install Web Server] ***********************************************************
changed: [frontend-1]

TASK [Start Web Server] ********************************************************
changed: [frontend-1]

PLAY RECAP *********************************************************************
frontend-1                 : ok=3    changed=2    unreachable=0    failed=0
```
Now we can view the [Apache Welcome Page](http://192.168.33.31/) running on our web server.
## Sudo
To perform tasks that requires ```sudo``` you need to pass in the ```become``` option in the playbook.
```yaml
- hosts: frontend
  become: true
```
This will run all tasks with ```sudo```

You can run individual tasks with ```sudo``` as well by adding it to the task
```yaml
- hosts: frontend
  tasks:
    - name: Install Web Server
      become: true
      yum:
        name: httpd
      	state: present
```
You can also become a different user using the ```become_user``` option.
```yaml
- hosts: frontend
  tasks:
    - name: Create Website Directory
      become: true
      become_user: apache
      file:
        path: /var/www/website
      	state: directory
```
## Handlers
Handlers enable us to perform operations only on a change.
```yaml
  handlers:
    - name: Restart Web Server
      service:
        name: httpd
        state: restarted
```
We can call this handler with ```notify``` with:
```yaml
  tasks:
    - name: Install PHP
      yum:
        name: php
        state: present
      notify:
        - Restart Web Server
```
Pearl is wants PHP to be installed on her web server.

We can run the playbook to install PHP on our webserver.
```bash
ansible-playbook \
    -i inventory/inventory.ini \
    install_php.yml
```
We can see that PHP is installed and then our webserver has been restarted.
```
PLAY [frontend-1] **************************************************************

TASK [setup] *******************************************************************
ok: [frontend-1]

TASK [Install PHP] *************************************************************
changed: [frontend-1]

RUNNING HANDLER [Restart Web Server] *******************************************
changed: [frontend-1]

PLAY RECAP *********************************************************************
frontend-1                 : ok=3    changed=2    unreachable=0    failed=0
```
When the playbook is completed the handlers will be run if they are needed.
The handlers will be run once so we can call ```notify``` as many times as we want.
# Roles
Roles enable us to organise our playbooks.

We can now structure our project like below:
```
root
├── inventory
│   └── inventory.ini
├── roles
│   ├── httpd
│   │   └── tasks
│   │       └── main.yml
│   ├── php
│   │   ├── tasks
│   │   │   └── main.yml
│   │   └── handlers
│   │       └── main.yml
│   └── website
│       └── tasks
│           └── main.yml
└── build_website.yml
```
Now we can call each role from my playbook
```yaml
- hosts: frontend-1
  become: true
  roles:
    - httpd
    - php
    - website
```
We can now structure tasks in each roles like:
```yaml
- name: Install Web Server
  yum:
    name: httpd
    state: present

- name: Start Webserver
  service:
    name: httpd
    enabled: yes
    state: started
```
When we run the playbook, the tasks in each role is run
```bash
ansible-playbook \
    -i inventory/inventory.ini \
    build_website.yml
```
We can also see the role name in the output
```
PLAY [frontend-1] **************************************************************

TASK [setup] *******************************************************************
ok: [frontend-1]

TASK [httpd : Install Web Server] **********************************************
ok: [frontend-1]

TASK [httpd : Start Webserver] *************************************************
changed: [frontend-1]

TASK [php : Install PHP] *******************************************************
ok: [frontend-1]

TASK [php : Install PHP Common] ************************************************
ok: [frontend-1]

TASK [website : Install Website] ***********************************************
changed: [frontend-1]

TASK [website : stat] **********************************************************
ok: [frontend-1]

TASK [website : Install Predis Library] ****************************************
changed: [frontend-1]

PLAY RECAP *********************************************************************
frontend-1                 : ok=8    changed=3    unreachable=0    failed=0 
```
We can easily reuse these roles.
# Running On Multiple Hosts
Pearl expects her website to be in very high demand.
So she would like her website to be installed on three webservers.
```yaml
- hosts: frontend
  become: true
  roles:
    - httpd
    - php
    - website
```
A load balancer will be set up on a forth server.
```yaml
- name: Install HA Proxy
  yum:
    name: haproxy
    state: present

- name: Install HA Proxy Configuration
  template:
    src: haproxy.cfg
    dest: /etc/haproxy/haproxy.cfg

- name: Start HA Proxy
  service:
    name: haproxy
    state: started
```
We can now configure three webservers and a load balancer:
```bash
ansible-playbook \
    -i inventory/inventory.ini \
    load_balanced_website.yml
```
We can see from the output that we are managing all our webservers concurrently
```
PLAY [frontend] ****************************************************************

TASK [setup] *******************************************************************
ok: [frontend-3]
ok: [frontend-1]
ok: [frontend-2]

TASK [httpd : Install Web Server] **********************************************
ok: [frontend-1]
changed: [frontend-2]
changed: [frontend-3]

TASK [httpd : Start Webserver] *************************************************
ok: [frontend-1]
changed: [frontend-3]
changed: [frontend-2]

TASK [php : Install PHP] *******************************************************
ok: [frontend-1]
changed: [frontend-2]
changed: [frontend-3]

TASK [php : Install PHP Common] ************************************************
ok: [frontend-1]
ok: [frontend-2]
ok: [frontend-3]

TASK [website : Install Website] ***********************************************
ok: [frontend-1]
changed: [frontend-3]
changed: [frontend-2]

TASK [website : stat] **********************************************************
ok: [frontend-1]
ok: [frontend-2]
ok: [frontend-3]

TASK [website : Install Predis Library] ****************************************
skipping: [frontend-1]
changed: [frontend-2]
changed: [frontend-3]

RUNNING HANDLER [php : Restart Web Server] *************************************
changed: [frontend-2]
changed: [frontend-3]

PLAY [load-balancer] ***********************************************************

TASK [setup] *******************************************************************
ok: [load-balancer-1]

TASK [haproxy : Install HA Proxy] **********************************************
changed: [load-balancer-1]

TASK [haproxy : Install HA Proxy Configuration] ********************************
changed: [load-balancer-1]

TASK [haproxy : Start HA Proxy] ************************************************
changed: [load-balancer-1]

PLAY RECAP *********************************************************************
frontend-1                 : ok=7    changed=0    unreachable=0    failed=0   
frontend-2                 : ok=9    changed=6    unreachable=0    failed=0   
frontend-3                 : ok=9    changed=6    unreachable=0    failed=0   
load-balancer-1            : ok=4    changed=3    unreachable=0    failed=0
```
Now [Pearls Website](http://192.168.33.34/) from our load balancer.
There is no chance of her website falling over when there is high demand!
# Docker
To demonstrate how Ansible will help us with micro services we will use Docker.

Pearl will need Redis to keep track of her visitors so we set up a Redis instance on a new server.

Our role would include this task:
```yaml
- name: Create Redis Container
  docker_container:
    name: redis
    image: redis
    command: redis-server --appendonly yes
    state: started
    exposed_ports:
      - 6379
    volumes:
      - /redisdata
```
For this example we need to use Ansible Galaxy to help us be DRY
```bash
ansible-galaxy install geerlingguy.repo-epel
```
Our playbook looks like this
```yaml
- hosts: docker
  become: true
  roles:
    - geerlingguy.repo-epel
    - docker
```
When the playbook is run docker will be installed
```bash
ansible-playbook \
    -i inventory/inventory.ini \
    docker.yml
```
We can see the docker container being started.
```
PLAY [docker] ******************************************************************

TASK [setup] *******************************************************************
ok: [docker-1]

TASK [geerlingguy.repo-epel : Check if EPEL repo is already configured.] *******
ok: [docker-1]

TASK [geerlingguy.repo-epel : Install EPEL repo.] ******************************
changed: [docker-1]

TASK [geerlingguy.repo-epel : Import EPEL GPG key.] ****************************
changed: [docker-1]

TASK [docker : Install Docker] *************************************************
changed: [docker-1]

TASK [docker : Start Docker] ***************************************************
changed: [docker-1]

TASK [docker : stat] ***********************************************************
ok: [docker-1]

TASK [docker : Install Pip] ****************************************************
changed: [docker-1]

TASK [docker : Install Python Docker] ******************************************
changed: [docker-1]

TASK [docker : Create Redis Container] *****************************************
changed: [docker-1]

PLAY RECAP *********************************************************************
docker-1                   : ok=10   changed=7    unreachable=0    failed=0
```
