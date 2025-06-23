report({
  "testSuite": "BackstopJS",
  "tests": [
    {
      "pair": {
        "reference": "../bitmaps_reference/vrt_1746450990_Test_Scenario_0_document_0_desktop.png",
        "test": "../bitmaps_test/20250505-184639/vrt_1746450990_Test_Scenario_0_document_0_desktop.png",
        "selector": "document",
        "fileName": "vrt_1746450990_Test_Scenario_0_document_0_desktop.png",
        "label": "Test Scenario",
        "requireSameDimensions": true,
        "misMatchThreshold": 0.1,
        "url": "http://localhost:10099/netflix-clone/",
        "referenceUrl": "https://en.wikipedia.org/wiki/Holi",
        "expect": 0,
        "viewportLabel": "desktop",
        "diff": {
          "isSameDimensions": false,
          "dimensionDifference": {
            "width": 0,
            "height": -19366
          },
          "rawMisMatchPercentage": 2.2409561161595613,
          "misMatchPercentage": "2.24",
          "analysisTime": 507
        },
        "diffImage": "../bitmaps_test/20250505-184639/failed_diff_vrt_1746450990_Test_Scenario_0_document_0_desktop.png"
      },
      "status": "fail"
    }
  ],
  "id": "vrt_1746450990"
});