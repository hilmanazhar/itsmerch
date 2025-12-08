const Avatar = ({ src, alt, size = 'medium' }) => {
    const sizeClasses = {
        small: 'rounded-circle',
        medium: 'rounded-circle',
        large: 'rounded-circle',
    };

    return (
        <img
            src={src}
            alt={alt}
            className={`img-fluid ${sizeClasses[size]}`}
            style={{
                width: size === 'small' ? '30px' : size === 'large' ? '100px' : '50px',
                height: size === 'small' ? '30px' : size === 'large' ? '100px' : '50px',
            }}
        />
    );
};

export default Avatar;