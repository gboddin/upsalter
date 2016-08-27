{% if grains['os_family'] == 'RedHat' %}

yum:
  pkg.latest

edge-repo:
  # repository :
  pkgrepo.managed:
    - humanname: {{ grains['os'] }}-$releasever - Edge repo
    - baseurl: http://repo.siwhine.net/{{ grains['os']|lower() }}-{{ grains['osmajorrelease'] }}
    - gpgcheck: 1
    - gpgkey: http://repo.siwhine.net/EDGE-REPO-KEY.pub
    - require:
      - pkg: yum
{% endif %}