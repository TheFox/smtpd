# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  config.vm.box = "generic/debian9"
  config.vm.box_check_update = false

  config.vm.hostname = 'smtpd'
  config.vm.network "forwarded_port", guest: 20025, host: 20025

  config.vm.synced_folder ".", "/app"

  config.vm.provider "virtualbox" do |vb|
    vb.gui = false
    vb.memory = 1024
  end

  config.vm.provision "shell" do |s|
    s.env = {
      'DEBIAN_FRONTEND' => 'noninteractive',
      'PHP_VERSION' => '7.0',
      'WORKING_DIR' => '/app',
    }
    s.inline = <<-SHELL
      echo "cd ${WORKING_DIR}" >> /home/vagrant/.bashrc
      echo "export PHP_IDE_CONFIG='serverName=vagrant'" >> /home/vagrant/.bashrc
      
      apt-get install -y apt-transport-https ca-certificates
      
      cp ${WORKING_DIR}/php/php.list /etc/apt/sources.list.d/php.list
      [[ ! -f /etc/apt/trusted.gpg.d/php.gpg ]] && wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
      
      apt-get update -yqq
      apt-get upgrade -y
      apt-get install -y htop vim lsof net-tools rsync zlib1g-dev git php${PHP_VERSION}-dev php${PHP_VERSION}-cli php${PHP_VERSION}-zip php${PHP_VERSION}-xml php${PHP_VERSION}-mbstring composer

      netstat -rn | grep "^0.0.0.0 " | cut -d " " -f10 > /tmp/host_ip.txt
      host_ip=$(cat /tmp/host_ip.txt)

      cp ${WORKING_DIR}/php/php.ini /etc/php/${PHP_VERSION}/cli/php.ini

      pecl install xdebug
      sed -e "s/@HOST_IP@/$host_ip/g" ${WORKING_DIR}/php/ext/xdebug.ini > /etc/php/${PHP_VERSION}/cli/conf.d/20-xdebug.ini
      
      echo 'done'
    SHELL
  end
end
