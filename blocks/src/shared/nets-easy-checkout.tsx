/**
 * External dependencies
 */
import * as React from "react";

type NetsEasyCheckoutProps = {
  description: string;
};

export const NetsEasyCheckout: React.FC<NetsEasyCheckoutProps> = (props) => {
  const { description } = props;
  return (
    <div>
      <p>{description}</p>
    </div>
  );
};

type LabelProps = {
  title: string;
  icon: string;
};

export const Label: React.FC<LabelProps> = (props) => {
  const { title, icon } = props;
  // Print the title and icon as a single line.
  return (
    <div
      style={{
        display: "flex",
        gap: 16,
        width: "100%",
        justifyContent: "space-between",
        paddingRight: 16,
      }}
    >
      <span>{title}</span>
      <img src={icon} alt={title} />
    </div>
  );
};
