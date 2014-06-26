  function ajax(urlpassed,dynamicData) {
    return $.ajax({
      url: urlpassed,
      type: "post",
      data: dynamicData
    });
  }
