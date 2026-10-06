import React from "react";
import ReactDOM from "react-dom";
import { MoralisProvider } from "react-moralis";

ReactDOM.render(
  <MoralisProvider appId="xxxxxxxx" serverUrl="xxxxxxxx">
    <App />
  </MoralisProvider>,
  document.getElementById("root"),
);