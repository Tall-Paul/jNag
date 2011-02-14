
/*
 * PhoneGap is available under *either* the terms of the modified BSD license *or* the
 * MIT License (2008). See http://opensource.org/licenses/alphabetical for full text.
 * 
 * Copyright (c) 2005-2010, Nitobi Software Inc.
 * Copyright (c) 2010, IBM Corporation
 */

/**
 * This represents the mobile device, and provides properties for inspecting the model, version, UUID of the
 * phone, etc.
 * @constructor
 */
function Device() {
  this.platform = phonegap.device.platform;
  this.version  = blackberry.system.softwareVersion;
  this.name     = blackberry.system.model;
  this.uuid     = phonegap.device.uuid;
  this.phonegap = phonegap.device.phonegap;
};

PhoneGap.addConstructor(function() {
  navigator.device = window.device = new Device();
  PhoneGap.onPhoneGapInfoReady.fire();
});
