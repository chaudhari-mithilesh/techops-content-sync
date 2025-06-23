report({
  "testSuite": "BackstopJS",
  "tests": [
    {
      "pair": {
        "reference": "../bitmaps_reference/vrt_1746451287_Test_Scenario_0_document_0_desktop.png",
        "test": "../bitmaps_test/20250505-185205/vrt_1746451287_Test_Scenario_0_document_0_desktop.png",
        "selector": "document",
        "fileName": "vrt_1746451287_Test_Scenario_0_document_0_desktop.png",
        "label": "Test Scenario",
        "requireSameDimensions": false,
        "misMatchThreshold": 5,
        "url": "https://wisdmlabs:wisdm101@playmeo.wisdmlabs.net/",
        "referenceUrl": "https://playmeo:playmeostaging@playmeo.staging.tempurl.host/",
        "expect": 0,
        "viewportLabel": "desktop",
        "diff": {
          "isSameDimensions": false,
          "dimensionDifference": {
            "width": 0,
            "height": -155
          },
          "rawMisMatchPercentage": 31.21762054135101,
          "misMatchPercentage": "31.22",
          "analysisTime": 309
        },
        "diffImage": "../bitmaps_test/20250505-185205/failed_diff_vrt_1746451287_Test_Scenario_0_document_0_desktop.png"
      },
      "status": "fail"
    }
  ],
  "id": "vrt_1746451287"
});