(function () {
  var config = window.vxSendificoAddressForm || null;

  if (!config || !config.enabled) {
    return;
  }

  function normalize(value) {
    return (value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toUpperCase()
      .replace(/[^A-Z0-9]+/g, '_')
      .replace(/^_+|_+$/g, '');
  }

  function createPlaceholder(label) {
    var option = document.createElement('option');
    option.value = '';
    option.textContent = label;

    return option;
  }

  function clearOptions(select, placeholder) {
    select.innerHTML = '';
    select.appendChild(createPlaceholder(placeholder));
  }

  function appendOptions(select, collection) {
    Object.keys(collection).forEach(function (key) {
      var option = document.createElement('option');
      option.value = collection[key].label;
      option.textContent = collection[key].label;
      select.appendChild(option);
    });
  }

  function buildCitySelect(cityInput) {
    var citySelect = document.createElement('select');
    citySelect.className = cityInput.className || 'form-control';
    citySelect.setAttribute('data-vx-sendifico-city-select', '1');
    citySelect.required = cityInput.required;

    cityInput.type = 'hidden';
    cityInput.parentNode.insertBefore(citySelect, cityInput.nextSibling);

    return citySelect;
  }

  function findSelectedText(select) {
    if (!select || select.selectedIndex < 0) {
      return '';
    }

    return select.options[select.selectedIndex].text || '';
  }

  function queryByNames(form, names) {
    var i;

    for (i = 0; i < names.length; i += 1) {
      var field = form.querySelector('[name="' + names[i] + '"]');
      if (field) {
        return field;
      }
    }

    return null;
  }

  function enhanceAddressForm(form) {
    if (!form || form.getAttribute('data-vx-sendifico-enhanced') === '1') {
      return;
    }

    var countrySelect = form.querySelector('[name="id_country"]');
    var stateSelect = form.querySelector('[name="id_state"]');
    var cantonSelect = queryByNames(form, [config.fieldCanton, 'sendifico_canton']);
    var territoryInput = queryByNames(form, [config.fieldTerritoryBaseId, 'sendifico_territory_base_id']);
    var cityInput = form.querySelector('[name="city"]');

    if (!countrySelect || !stateSelect || !cantonSelect || !territoryInput || !cityInput) {
      return;
    }

    var citySelect = form.querySelector('[data-vx-sendifico-city-select]') || buildCitySelect(cityInput);
    form.setAttribute('data-vx-sendifico-enhanced', '1');
    var stateGroup = stateSelect.closest('.form-group');
    var cantonGroup = cantonSelect.closest('.form-group');
    var cityGroup = cityInput.closest('.form-group');

    if (stateGroup && cantonGroup) {
      stateGroup.parentNode.insertBefore(cantonGroup, stateGroup.nextSibling);
    }

    if (cantonGroup && cityGroup) {
      cantonGroup.parentNode.insertBefore(cityGroup, cantonGroup.nextSibling);
    }

    function isConfiguredCountry() {
      return parseInt(countrySelect.value || '0', 10) === parseInt(config.configuredCountryId || '0', 10);
    }

    function getStateNode() {
      var stateKey = normalize(findSelectedText(stateSelect));

      return config.territories[stateKey] || null;
    }

    function getCantonNode() {
      var stateNode = getStateNode();
      if (!stateNode) {
        return null;
      }

      return stateNode.cantons[normalize(cantonSelect.value)] || null;
    }

    function syncHiddenTerritory() {
      var cantonNode = getCantonNode();
      if (!cantonNode) {
        territoryInput.value = '';
        cityInput.value = citySelect.value || '';

        return;
      }

      var cityNode = cantonNode.cities[normalize(citySelect.value)];
      territoryInput.value = cityNode ? cityNode.territoryBaseId : '';
      cityInput.value = citySelect.value || '';
    }

    function populateCantons() {
      var stateNode = getStateNode();
      var currentCanton = cantonSelect.value;
      clearOptions(cantonSelect, 'Select canton');

      if (!stateNode) {
        populateCities();

        return;
      }

      appendOptions(cantonSelect, stateNode.cantons);
      if (currentCanton && stateNode.cantons[normalize(currentCanton)]) {
        cantonSelect.value = currentCanton;
      }

      populateCities();
    }

    function populateCities() {
      var cantonNode = getCantonNode();
      var currentCity = cityInput.value || citySelect.value;
      clearOptions(citySelect, 'Select city');

      if (!cantonNode) {
        syncHiddenTerritory();

        return;
      }

      appendOptions(citySelect, cantonNode.cities);
      if (currentCity && cantonNode.cities[normalize(currentCity)]) {
        citySelect.value = cantonNode.cities[normalize(currentCity)].label;
      }

      syncHiddenTerritory();
    }

    function setNativeMode() {
      citySelect.style.display = 'none';
      cityInput.type = 'text';
      if (cantonGroup) {
        cantonGroup.style.display = 'none';
      }
      territoryInput.value = '';
    }

    function setSendificoMode() {
      cityInput.type = 'hidden';
      citySelect.style.display = '';
      if (cantonGroup) {
        cantonGroup.style.display = '';
      }
      populateCantons();
    }

    function refreshMode() {
      if (!isConfiguredCountry()) {
        setNativeMode();

        return;
      }

      setSendificoMode();
    }

    countrySelect.addEventListener('change', refreshMode);
    stateSelect.addEventListener('change', populateCantons);
    cantonSelect.addEventListener('change', populateCities);
    citySelect.addEventListener('change', syncHiddenTerritory);

    refreshMode();
  }

  function enhanceAllAddressForms(root) {
    (root || document).querySelectorAll('.js-address-form form').forEach(enhanceAddressForm);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      enhanceAllAddressForms(document);
    });
  } else {
    enhanceAllAddressForms(document);
  }

  if (window.prestashop && typeof window.prestashop.on === 'function') {
    window.prestashop.on('updatedAddressForm', function (event) {
      if (event && event.target && event.target.length && event.target[0]) {
        enhanceAllAddressForms(event.target[0]);
        return;
      }

      enhanceAllAddressForms(document);
    });
  }

  if (window.MutationObserver) {
    new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (!node || node.nodeType !== 1) {
            return;
          }

          if (node.matches && node.matches('.js-address-form form')) {
            enhanceAddressForm(node);
            return;
          }

          enhanceAllAddressForms(node);
        });
      });
    }).observe(document.body, { childList: true, subtree: true });
  }
})();
