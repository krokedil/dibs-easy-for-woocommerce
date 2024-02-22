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
};

export const Label: React.FC<LabelProps> = (props) => {
  const { title } = props;
  return <span>{title}</span>;
};
