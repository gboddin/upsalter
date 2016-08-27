{% if grains['os_family'] == 'RedHat' %}

edge-repo:
  # repository :
  pkgrepo.managed:
    - humanname: {{ grains['os'] }}-$releasever - Edge repo
    - baseurl: http://repo.siwhine.net/{{ grains['osfinger']|lower() }}
    - gpgcheck: 1
    - gpgkey: http://repo.siwhine.net/EDGE-REPO-KEY.pub

{% endif %}