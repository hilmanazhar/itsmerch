// This file defines the Aspect Ratio component, ensuring elements maintain a specific ratio.

function AspectRatio({ ratio, children }) {
    const aspectRatioStyle = {
        position: 'relative',
        width: '100%',
        paddingTop: `${(1 / ratio) * 100}%`, // Aspect ratio calculation
        overflow: 'hidden',
    };

    const childStyle = {
        position: 'absolute',
        top: 0,
        left: 0,
        width: '100%',
        height: '100%',
        objectFit: 'cover', // Ensures the child fits within the aspect ratio
    };

    return (
        <div style={aspectRatioStyle} className="aspect-ratio">
            {React.Children.map(children, (child) =>
                React.cloneElement(child, { style: childStyle })
            )}
        </div>
    );
}

// Export the AspectRatio component for use in other parts of the application
export default AspectRatio;