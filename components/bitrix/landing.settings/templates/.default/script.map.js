{"version":3,"sources":["script.js"],"names":["this","BX","Landing","exports","main_core","main_loader","_templateObject","LandingSettings","options","_this","babelHelpers","classCallCheck","siteId","landingId","pages","container","document","getElementById","containerId","loader","Loader","target","page","Tag","render","taggedTemplateLiteral","Dom","append","loadingPages","links","menuId","querySelectorAll","currentLink","forEach","link","Event","bind","event","preventDefault","stopPropagation","onLinkClick","dataset","current","saveButton","saveButtonId","onSave","createClass","key","value","onPageChange","placement","rest","Marketplace","bindPageAnchors","AppLayout","openApplication","appId","SITE_ID","LID","PLACEMENT","PLACEMENT_ID","placementId","_this2","currPage","childNodes","length","show","push","ajax","get","result","innerHTML","splice","indexOf","hide","form","querySelector","_page","hidden","_this3","submits","fetch","linkToSave","method","body","FormData","headers","Promise","all","then","results","ok","top","window","onCustomEvent","location","reload","SidePanel","Instance","close","err","console","error","Component"],"mappings":"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","file":"script.map.js"}