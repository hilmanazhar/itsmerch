const SectionHeader = ({ title }) => {
    return (
        <div className="section-header text-center my-4">
            <h2 className="display-4">{title}</h2>
            <hr className="my-4" />
        </div>
    );
};

export default SectionHeader;