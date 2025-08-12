javascript:(function(){
  const originalComplete = window.g_oSearchResults.OnAJAXComplete;
  window.g_oSearchResults.OnAJAXComplete = function(...args) {
    originalComplete.apply(this, args);
    setTimeout(() => {
      const id = prompt("ID");
      const img = document.getElementById("listing_"+id+"_image");
      if (img) {
        img.scrollIntoView({behavior:"smooth", block:"center"});
        img.style.border = "3px solid red";
      } else {
        alert("Listing not found");
      }
      window.g_oSearchResults.OnAJAXComplete = originalComplete;
    }, 2000);
  };

  window.g_oSearchResults.m_cPageSize = 100;
  window.g_oSearchResults.GoToPage(0, true);
})();