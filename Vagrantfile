Vagrant.configure("2") do |config|
  config.vm.box = "wheezy-72-64-vb-43"
    config.vm.box_url = "https://dl.dropboxusercontent.com/u/197673519/debian-7.2.0.box"
    config.vm.provision :puppet do |puppet|
    puppet.manifests_path = "build/puppet/manifests"
  end
  config.vm.network :forwarded_port, host: 2345, guest: 80
  config.vm.provider "virtualbox" do |v|
    v.gui = false
  end
end
