{% if grains['os_family'] == 'RedHat' %}

yum:
  pkg.latest

fpfis-repo:
  # repository :
  pkgrepo.managed:
    - humanname: {{ grains['os'] }}-$releasever - Edge repo
    - baseurl: http://repo.ne-dev.eu/el/{{ grains['osmajorrelease'] }}
    - gpgcheck: 0
    - require:
      - pkg: yum
{% endif %}
