const Label = ({ text, htmlFor }) => {
    return (
        <label htmlFor={htmlFor} className="form-label">
            {text}
        </label>
    );
};

export default Label;