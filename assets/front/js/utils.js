export const getStepByName = (name) => {
  return window.allSteps.find((step) => step.name === name);
};

export const getStepColorRGBA = (step, alpha) => {
  const rgb = getComputedStyle(document.body).getPropertyValue(
    `--${step.name}`
  );
  return `rgba(${rgb}, ${alpha ?? 1})`;
};

export const camelToSnakeCase = (str) =>
  str.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);

export const styleNames = (styles) =>
  Object.entries(styles).reduce((pre, [styleProp, styleVal]) => {
    pre += `${camelToSnakeCase(styleProp)}: ${styleVal}; `;
    return pre;
  }, "");
